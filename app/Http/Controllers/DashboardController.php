<?php

namespace App\Http\Controllers;

use App\Models\GeospatialWorkspace;
use App\Models\Project;
use App\Models\Province;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\WarningStation;
use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AuthorizationService $authorization)
    {
    }

    public function index(Request $request): View
    {
        return view('modules.dashboard.index', [
            'projects' => $this->accessibleProjects($request),
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

    public function mapData(Request $request): JsonResponse
    {
        return $this->noStoreJson($this->mapPayload($this->accessibleProjects($request)->modelKeys()));
    }

    private function accessibleProjects(Request $request): Collection
    {
        if (! Schema::hasTable('resq_projects')) {
            return new Collection();
        }

        $query = Project::with(['client', 'workspaces'])
            ->withCount(['workspaces', 'monitoringStations', 'warningStations'])
            ->orderBy('name');
        $this->authorization->scopeProjectsForUser($request->user(), $query);

        return $query->get()
            ->filter(fn (Project $project) => $this->authorization->canAccessProject($request->user(), $project))
            ->values();
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
            ->filter(fn ($item) => $item['lat'] !== null && $item['lng'] !== null)
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
