<?php

namespace App\Http\Controllers;

use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Province;
use App\Models\Sensor;
use App\Models\StationFunctionConfiguration;
use App\Models\TelemetryReading;
use App\Models\WarningStation;
use App\Services\AuthorizationService;
use App\Services\TrendDiagnosisAnalyticalFunctionRunner;
use App\Services\TrendDiagnosisEvaluator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AuthorizationService $authorization)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', Rule::in([10, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $projects = new LengthAwarePaginator([], 0, $perPage, $filters['page'] ?? 1, [
            'path' => $request->url(), 'query' => $filters,
        ]);
        $provinces = $cities = collect();
        $totalProjects = 0;

        if (Schema::hasTable('resq_projects')) {
            $query = $this->accessibleProjectQuery($request);
            $totalProjects = (clone $query)->count();
            $workspaces = GeospatialWorkspace::whereIn('project_id', (clone $query)->select('resq_projects.id'));
            $provinces = (clone $workspaces)->whereNotNull('province')->where('province', '!=', '')
                ->distinct()->orderBy('province')->pluck('province');
            $cities = (clone $workspaces)->when($filters['province'] ?? null, fn ($q, $province) => $q->where('province', $province))
                ->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city');
            $search = trim($filters['q'] ?? '');
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->whereLike('name', '%'.$search.'%')->orWhereLike('project_code', '%'.$search.'%')
                        ->orWhereLike('owner', '%'.$search.'%')
                        ->orWhereHas('client', fn ($client) => $client->whereLike('name', '%'.$search.'%'));
                });
            }
            if (! empty($filters['province']) || ! empty($filters['city'])) {
                $query->whereHas('workspaces', function (Builder $workspace) use ($filters) {
                    $workspace->when($filters['province'] ?? null, fn ($q, $province) => $q->where('province', $province))
                        ->when($filters['city'] ?? null, fn ($q, $city) => $q->where('city', $city));
                });
            }
            $projectPriorities = $this->mapSensors((clone $query)->pluck('resq_projects.id')->all())
                ->groupBy('project_id')
                ->map(fn ($sensors) => $sensors->max(fn ($sensor) => $this->sensorPriority($sensor)));
            $prioritySql = 'CASE';
            $priorityBindings = [];
            foreach ($projectPriorities->groupBy(fn ($priority) => $priority, preserveKeys: true) as $priority => $group) {
                $prioritySql .= ' WHEN resq_projects.id IN ('.implode(',', array_fill(0, $group->count(), '?')).') THEN ?';
                array_push($priorityBindings, ...$group->keys()->all());
                $priorityBindings[] = (int) $priority;
            }
            if ($priorityBindings) {
                $query->orderByRaw($prioritySql.' ELSE -1 END DESC', $priorityBindings);
            }
            $projects = $query->with(['client', 'workspaces'])->withCount(['workspaces', 'monitoringStations', 'warningStations'])
                ->orderBy('name')->orderBy('id')->paginate($perPage)->appends($filters);
            $labels = [3 => ['Awas', 'danger'], 2 => ['Siaga', 'warning'], 1 => ['Waspada', 'warning'], 0 => ['Normal', 'success'], -1 => ['Belum diketahui', 'secondary']];
            foreach ($projects as $project) {
                [$project->monitoring_status, $project->monitoring_tone] = $labels[$projectPriorities->get($project->id, -1)];
            }
        }

        if ($request->expectsJson()) {
            return $this->noStoreJson([
                'html' => view('modules.dashboard.partials.project-list', compact('projects', 'totalProjects'))->render(),
                'count_label' => $projects->total().' dari '.$totalProjects.' Project',
                'project_ids' => $projects->getCollection()->modelKeys(),
            ]);
        }

        return view('modules.dashboard.index', [
            'projects' => $projects,
            'filters' => $filters,
            'provinces' => $provinces,
            'cities' => $cities,
            'totalProjects' => $totalProjects,
        ]);
    }

    public function projectMap(Request $request, Project $project): View
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $project), 403);
        $payload = $this->mapPayload([$project->id]);

        return view('modules.dashboard.map', [
            'project' => $project,
            'mapClusters' => $payload['clusters'],
            'mapSensors' => $payload['sensors'],
            'mapWarningStations' => $payload['warningStations'],
            'dashboardAlertActive' => $payload['alert_active'],
            'projectMonitoringStations' => MonitoringStation::where('project_id', $project->id)->orderBy('station_code')->pluck('station_code'),
            'projectWeatherLocations' => $project->workspaces()->orderBy('id')->get(['city', 'latitude', 'longitude'])
                ->map(fn ($workspace) => ['city' => $workspace->city, 'lat' => $workspace->latitude, 'lng' => $workspace->longitude])
                ->concat($project->monitoringStations()->orderBy('id')->get(['latitude', 'longitude'])
                    ->map(fn ($station) => ['city' => null, 'lat' => $station->latitude, 'lng' => $station->longitude]))
                ->values(),
        ]);
    }

    public function projectMapData(Request $request, Project $project): JsonResponse
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $project), 403);

        return $this->noStoreJson($this->mapPayload([$project->id]));
    }

    public function projectTdeData(Request $request, Project $project, TrendDiagnosisEvaluator $evaluator): JsonResponse
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $project), 403);

        return $this->noStoreJson([
            'stations' => $this->tdeStations([$project->id], $evaluator),
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function dashboardTdeData(Request $request, TrendDiagnosisEvaluator $evaluator): JsonResponse
    {
        $data = $request->validate([
            'project_ids' => ['required', 'array', 'min:1', 'max:50'],
            'project_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = array_map('intval', $data['project_ids']);
        abort_unless($this->accessibleProjectQuery($request)->whereIn('id', $ids)->count() === count($ids), 403);

        return $this->noStoreJson([
            'stations' => $this->tdeStations($ids, $evaluator),
            'generated_at' => now()->toISOString(),
        ]);
    }

    private function tdeStations(array $projectIds, TrendDiagnosisEvaluator $evaluator)
    {
        return MonitoringStation::whereIn('project_id', $projectIds)
            ->with(['project', 'workspace', 'functionConfigurations', 'sensors.mappingProfile.canonicalParameter', 'sensors.mappingProfiles.canonicalParameter'])
            ->orderBy('project_id')->orderBy('station_code')->get()
            ->map(function (MonitoringStation $station) use ($evaluator) {
                $configuration = $station->functionConfigurations->firstWhere('function_name', StationFunctionConfiguration::FUNCTION_TDE);

                return [
                    'id' => $station->id,
                    'station_code' => $station->station_code,
                    'name' => $station->name,
                    'project_id' => $station->project_id,
                    'project_name' => $station->project?->name,
                    'project_code' => $station->project?->project_code,
                    'forecast_url' => route('tde-forecast.index', ['station_id' => $station->id], false),
                    'lat' => $station->latitude ?? $station->workspace?->latitude,
                    'lng' => $station->longitude ?? $station->workspace?->longitude,
                    'tde' => (new TrendDiagnosisAnalyticalFunctionRunner($evaluator, $configuration))->run($station),
                ];
            })->values();
    }

    public function mapData(Request $request): JsonResponse
    {
        $projectIds = Schema::hasTable('resq_projects')
            ? $this->accessibleProjectQuery($request)->pluck('id')->all()
            : [];

        return $this->noStoreJson($this->mapPayload($projectIds));
    }

    private function accessibleProjectQuery(Request $request): Builder
    {
        $user = $request->user();
        $query = Project::query();
        $this->authorization->scopeProjectsForUser($user, $query);
        if ($user->isClientUser()) {
            if (! $user->client_id) {
                $query->whereRaw('1=0');
            } elseif (! $user->hasRole('ClientAdmin')) {
                $query->whereHas('users', fn ($users) => $users->where('users.id', $user->id));
            }
        }

        return $query;
    }

    private function mapPayload(array $projectIds): array
    {
        $payload = [
            'clusters' => $projectIds ? $this->mapClusters($projectIds) : collect(),
            'sensors' => $projectIds ? $this->mapSensors($projectIds) : collect(),
            'warningStations' => $projectIds ? $this->mapWarningStations($projectIds) : collect(),
            'generated_at' => now()->toISOString(),
        ];
        $payload['alert_active'] = $this->payloadHasActiveAlert($payload);

        return $payload;
    }

    private function noStoreJson(array $payload): JsonResponse
    {
        return response()
            ->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function provinceCoordinates(): array
    {
        if (! Schema::hasTable('provinces') || ! Schema::hasColumn('provinces', 'latitude')) {
            return [];
        }

        return Province::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->keyBy('name')
            ->map(fn (Province $province) => [
                'lat' => (float) $province->latitude,
                'lng' => (float) $province->longitude,
            ])
            ->all();
    }

    private function mapClusters(array $projectIds)
    {
        $provinceCoordinates = $this->provinceCoordinates();

        return GeospatialWorkspace::with(['sensors'])->withCount(['sensors', 'warningStations'])
            ->whereIn('project_id', $projectIds)
            ->get()
            ->map(function (GeospatialWorkspace $workspace) use ($provinceCoordinates) {
                $fallback = $provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null];
                $lat = $workspace->latitude ?: $fallback['lat'];
                $lng = $workspace->longitude ?: $fallback['lng'];
                $hasDangerSensor = $workspace->sensors->contains(fn (Sensor $sensor) => $this->isDangerStatus($sensor->status)
                    || $this->isDangerStatus($sensor->alert_level)
                    || $this->thresholdExceeded($sensor->value, $sensor->threshold));

                return [
                    'name' => $workspace->name,
                    'province' => $workspace->province,
                    'city' => $workspace->city,
                    'hazard' => $workspace->hazard,
                    'status' => $workspace->status,
                    'is_danger' => $this->isDangerStatus($workspace->status) || $hasDangerSensor,
                    'sensors' => $workspace->sensors_count,
                    'warnings' => $workspace->warning_stations_count,
                    'lat' => $lat ? (float) $lat : null,
                    'lng' => $lng ? (float) $lng : null,
                ];
            })
            ->filter(fn ($item) => $item['lat'] !== null && $item['lng'] !== null)
            ->values();
    }

    private function mapSensors(array $projectIds)
    {
        $provinceCoordinates = $this->provinceCoordinates();
        $sensorModels = Sensor::with(['workspace', 'monitoringStation', 'warningStation'])
            ->where(function ($query) use ($projectIds) {
                $query->whereHas('workspace', fn ($workspace) => $workspace->whereIn('project_id', $projectIds))
                    ->orWhereHas('monitoringStation', fn ($station) => $station->whereIn('project_id', $projectIds))
                    ->orWhereHas('warningStation', fn ($station) => $station->whereIn('project_id', $projectIds));
            })
            ->get();
        $sensorIds = $sensorModels->pluck('id');

        // Optimized: only fetch the latest reading per sensor using a subquery
        $latestReadings = TelemetryReading::query()
            ->whereIn('sensor_id', $sensorIds)
            ->whereIn('id', function ($query) use ($sensorIds) {
                $query->selectRaw('MAX(id)')
                    ->from('telemetry_readings')
                    ->whereIn('sensor_id', $sensorIds)
                    ->groupBy('sensor_id');
            })
            ->get()
            ->keyBy('sensor_id');

        return $sensorModels
            ->map(function (Sensor $sensor) use ($provinceCoordinates, $latestReadings) {
                $reading = $latestReadings->get($sensor->id);
                $workspace = $sensor->workspace;
                $station = $sensor->monitoringStation;
                $fallback = $workspace ? ($provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null]) : ['lat' => null, 'lng' => null];
                $lat = $station?->latitude ?: $workspace?->latitude ?: $fallback['lat'];
                $lng = $station?->longitude ?: $workspace?->longitude ?: $fallback['lng'];
                $value = $this->valueWithUnit($reading?->value ?? $sensor->value, $sensor->unit);
                $statusValue = $reading?->status ?? $sensor->status;
                $alertValue = $reading?->alert_level ?? $sensor->alert_level;
                $thresholdExceeded = $this->thresholdExceeded($value, $sensor->threshold);
                $isDanger = $this->isDangerStatus($statusValue)
                    || $this->isDangerStatus($alertValue)
                    || $thresholdExceeded;
                $status = $thresholdExceeded && ! $this->isDangerStatus($statusValue)
                    ? 'Awas'
                    : $statusValue;
                $alertLevel = $thresholdExceeded && ! $this->isDangerStatus($alertValue)
                    ? 'Awas'
                    : $alertValue;

                return [
                    'project_id' => $workspace?->project_id ?? $station?->project_id ?? $sensor->warningStation?->project_id,
                    'name' => $sensor->sensor_code,
                    'type' => $sensor->type,
                    'parameter' => $sensor->parameter,
                    'value' => $value,
                    'parameter_values' => collect($reading?->parameter_values ?? [])
                        ->filter(fn ($item) => is_array($item))
                        ->map(fn ($item) => array_merge($item, [
                            'value_text' => $this->valueWithUnit($item['value_text'] ?? ($item['value'] ?? null), $item['unit'] ?? null),
                        ]))
                        ->values(),
                    'threshold' => $sensor->threshold,
                    'unit' => $sensor->unit,
                    'alert_level' => $alertLevel,
                    'station' => $station?->station_code,
                    'warning_station' => $sensor->warningStation?->station_code,
                    'warning_station_name' => $sensor->warningStation?->name,
                    'province' => $workspace?->province,
                    'city' => $workspace?->city,
                    'status' => $status,
                    'last_seen' => optional($reading?->received_at ?? $sensor->last_seen_at)->diffForHumans(),
                    'received_at' => optional($reading?->received_at ?? $sensor->last_seen_at)->toISOString(),
                    'threshold_exceeded' => $thresholdExceeded,
                    'is_danger' => $isDanger,
                    'lat' => $lat ? (float) $lat : null,
                    'lng' => $lng ? (float) $lng : null,
                ];
            })
            ->sort(fn ($a, $b) => ($this->sensorPriority($b) <=> $this->sensorPriority($a)) ?: strnatcasecmp($a['name'], $b['name']))
            ->values();
    }

    private function sensorPriority(array $sensor): int
    {
        $ranks = ['AWAS' => 3, 'BAHAYA' => 3, 'DANGER' => 3, 'CRITICAL' => 3, 'SIAGA' => 2, 'WASPADA' => 1, 'WARNING' => 1, 'NORMAL' => 0, 'SIAP' => 0];

        $priority = max(
            $ranks[strtoupper(trim($sensor['status'] ?? ''))] ?? -1,
            $ranks[strtoupper(trim($sensor['alert_level'] ?? ''))] ?? -1,
            ($sensor['threshold_exceeded'] ?? false) ? 3 : -1
        );

        if ($priority <= 0 && collect([$sensor['status'] ?? '', $sensor['alert_level'] ?? ''])
            ->contains(fn ($status) => in_array(strtoupper(trim($status)), ['OFFLINE', 'UNKNOWN'], true))) {
            return -1;
        }

        return $priority;
    }

    private function mapWarningStations(array $projectIds)
    {
        $provinceCoordinates = $this->provinceCoordinates();

        return WarningStation::with(['workspace', 'sensors', 'monitoringStation'])
            ->whereIn('project_id', $projectIds)
            ->get()
            ->map(function (WarningStation $station) use ($provinceCoordinates) {
                $workspace = $station->workspace;
                $fallback = $workspace ? ($provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null]) : ['lat' => null, 'lng' => null];
                $lat = $station->latitude ?: $workspace?->latitude ?: $fallback['lat'];
                $lng = $station->longitude ?: $workspace?->longitude ?: $fallback['lng'];
                $dangerSensors = $station->sensors
                    ->filter(fn (Sensor $sensor) => $this->isDangerStatus($sensor->status)
                        || $this->isDangerStatus($sensor->alert_level)
                        || $this->thresholdExceeded($sensor->value, $sensor->threshold))
                    ->map(function (Sensor $sensor) {
                        $thresholdExceeded = $this->thresholdExceeded($sensor->value, $sensor->threshold);

                        return [
                            'name' => $sensor->sensor_code,
                            'parameter' => $sensor->parameter,
                            'value' => $this->valueWithUnit($sensor->value, $sensor->unit),
                            'threshold' => $sensor->threshold,
                            'alert_level' => $thresholdExceeded && ! $this->isDangerStatus($sensor->alert_level)
                                ? 'Awas'
                                : $sensor->alert_level,
                            'status' => $thresholdExceeded && ! $this->isDangerStatus($sensor->status)
                                ? 'Awas'
                                : $sensor->status,
                            'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
                            'threshold_exceeded' => $thresholdExceeded,
                        ];
                    })
                    ->values();

                return [
                    'id' => $station->id,
                    'station_code' => $station->station_code,
                    'name' => $station->station_code . ' - ' . $station->name,
                    'workspace' => $workspace?->name,
                    'monitoring_station' => $station->monitoringStation?->station_code,
                    'zone_id' => $station->zone_id,
                    'administrative_location' => $station->administrative_location,
                    'controller_id' => $station->controller_id,
                    'controller_status' => $station->controller_status,
                    'province' => $workspace?->province,
                    'city' => $workspace?->city,
                    'status' => $station->status,
                    'public_warning_enabled' => $station->public_warning_enabled,
                    'ack_response' => $station->ack_response,
                    'danger_sensors' => $dangerSensors,
                    'is_danger' => $dangerSensors->isNotEmpty(),
                    'lat' => $lat ? (float) $lat : null,
                    'lng' => $lng ? (float) $lng : null,
                ];
            })
            ->filter(fn ($item) => $item['lat'] !== null && $item['lng'] !== null)
            ->values();
    }

    private function isDangerStatus(?string $status): bool
    {
        return in_array($status, ['Danger', 'Bahaya', 'Awas', 'Siaga'], true);
    }

    private function payloadHasActiveAlert(array $payload): bool
    {
        $sensors = collect($payload['sensors'] ?? []);
        $warningStations = collect($payload['warningStations'] ?? []);

        return $sensors->contains(fn ($sensor) => $this->isRealtimeDangerItem($sensor))
            || $warningStations->contains(function ($station) {
                return collect($station['danger_sensors'] ?? [])
                    ->contains(fn ($sensor) => $this->isRealtimeDangerItem($sensor));
            });
    }

    private function isRealtimeDangerItem(array $item): bool
    {
        return ($item['threshold_exceeded'] ?? false)
            || $this->isDangerStatus($item['status'] ?? null)
            || $this->isDangerStatus($item['alert_level'] ?? null);
    }

    private function valueWithUnit(mixed $value, ?string $unit): string
    {
        $text = trim((string) ($value ?? '-'));
        $unit = trim((string) $unit);

        if ($text === '' || $text === '-' || $unit === '' || $unit === '0') {
            return $text === '' ? '-' : $text;
        }

        if (! preg_match('/-?\d+([,.]\d+)?/', $text)) {
            return $text;
        }

        return Str::endsWith(Str::lower($text), Str::lower($unit))
            ? $text
            : trim($text . ' ' . $unit);
    }

    private function thresholdExceeded($value, $threshold): bool
    {
        $numericValue = $this->numericFromText($value);
        $numericThreshold = $this->numericFromText($threshold);

        if ($numericValue === null || $numericThreshold === null) {
            return false;
        }

        return $numericValue > $numericThreshold;
    }

    private function numericFromText($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '.', (string) $value);

        if (preg_match('/-?\d+(\.\d+)?/', $normalized, $matches)) {
            return (float) $matches[0];
        }

        return null;
    }

}
