<?php

namespace App\Http\Controllers;

use App\Models\Client;
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

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', Rule::in([10, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $projects = new LengthAwarePaginator([], 0, $perPage, $filters['page'] ?? 1, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);
        $provinces = $cities = $statuses = $clients = collect();
        $totalProjects = 0;

        if (Schema::hasTable('resq_projects')) {
            $query = $this->accessibleProjectQuery($request);
            $totalProjects = (clone $query)->count();
            $workspaces = GeospatialWorkspace::whereIn('project_id', (clone $query)->select('resq_projects.id'));
            $provinces = (clone $workspaces)->whereNotNull('province')->where('province', '!=', '')
                ->distinct()->orderBy('province')->pluck('province');
            $cities = (clone $workspaces)->when($filters['province'] ?? null, fn ($q, $province) => $q->where('province', $province))
                ->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city');
            $statuses = (clone $query)->whereNotNull('status')->where('status', '!=', '')
                ->distinct()->orderBy('status')->pluck('status');
            $clients = Client::whereIn('id', (clone $query)->select('client_id'))->orderBy('name')->pluck('name', 'id');

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
            $projects = $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($filters['client_id'] ?? null, fn ($q, $clientId) => $q->where('client_id', $clientId))
                ->with(['client', 'workspaces'])->withCount(['workspaces', 'monitoringStations', 'warningStations'])
                ->orderBy('name')->orderBy('id')->paginate($perPage)->withQueryString();
        }

        return view('modules.dashboard.index', [
            'projects' => $projects,
            'filters' => $filters,
            'provinces' => $provinces,
            'cities' => $cities,
            'statuses' => $statuses,
            'clients' => $clients,
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

        $stations = MonitoringStation::where('project_id', $project->id)
            ->with(['workspace', 'functionConfigurations', 'sensors.mappingProfile.canonicalParameter', 'sensors.mappingProfiles.canonicalParameter'])
            ->orderBy('station_code')->get();

        return $this->noStoreJson([
            'stations' => $stations->map(function (MonitoringStation $station) use ($evaluator) {
                $configuration = $station->functionConfigurations->firstWhere('function_name', StationFunctionConfiguration::FUNCTION_TDE);

                return [
                    'id' => $station->id,
                    'station_code' => $station->station_code,
                    'name' => $station->name,
                    'lat' => $station->latitude ?? $station->workspace?->latitude,
                    'lng' => $station->longitude ?? $station->workspace?->longitude,
                    'tde' => (new TrendDiagnosisAnalyticalFunctionRunner($evaluator, $configuration))->run($station),
                ];
            })->values(),
            'generated_at' => now()->toISOString(),
        ]);
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
            ->values();
    }

    private function mapWarningStations(array $projectIds)
    {
        $provinceCoordinates = $this->provinceCoordinates();

        return WarningStation::with(['workspace', 'sensors'])
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
                    'name' => $station->station_code . ' - ' . $station->name,
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
