<?php

namespace App\Http\Controllers;

use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Province;
use App\Models\Sensor;
use App\Models\WarningStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('resq_projects')) {
            $clusters = collect(config('resq_dummy.clusters'));
            $sensors = collect(config('resq_dummy.sensors'));
            $monitoringStations = collect(config('resq_dummy.monitoring_stations'));
            $warningStations = collect(config('resq_dummy.warning_stations'));

            return view('modules.dashboard.index', [
                'dashboardTotals' => [
                    'projects' => 1,
                    'workspaces' => $clusters->count(),
                    'monitoring_stations' => $monitoringStations->count(),
                    'warning_stations' => $warningStations->count(),
                    'sensors' => $sensors->count(),
                    'provinces' => $clusters->pluck('province')->unique()->count(),
                ],
                'coverageRows' => $clusters->map(function ($cluster) use ($sensors) {
                    return [
                        'province' => $cluster['province'],
                        'workspaces' => 1,
                        'sensors' => $sensors->where('cluster_id', $cluster['id'])->count(),
                        'status' => $cluster['status'],
                    ];
                })->values(),
                'mapClusters' => $this->dummyMapClusters($clusters),
                'mapSensors' => $this->dummyMapSensors($sensors, $monitoringStations, $clusters),
                'mapWarningStations' => $this->dummyMapWarningStations($warningStations, $sensors, $clusters),
            ]);
        }

        $coverageRows = GeospatialWorkspace::query()
            ->selectRaw('province, COUNT(*) as workspaces, MAX(status) as status')
            ->groupBy('province')
            ->get()
            ->map(function ($row) {
                return [
                    'province' => $row->province,
                    'workspaces' => $row->workspaces,
                    'sensors' => Sensor::whereHas('workspace', fn ($query) => $query->where('province', $row->province))->count(),
                    'status' => $row->status,
                ];
            });

        return view('modules.dashboard.index', [
            'dashboardTotals' => [
                'projects' => Project::count(),
                'workspaces' => GeospatialWorkspace::count(),
                'monitoring_stations' => MonitoringStation::count(),
                'warning_stations' => WarningStation::count(),
                'sensors' => Sensor::count(),
                'provinces' => GeospatialWorkspace::distinct('province')->count('province'),
            ],
            'coverageRows' => $coverageRows,
            'mapClusters' => $this->mapClusters(),
            'mapSensors' => $this->mapSensors(),
            'mapWarningStations' => $this->mapWarningStations(),
        ]);
    }

    public function mapData(): JsonResponse
    {
        if (! Schema::hasTable('resq_projects')) {
            $clusters = collect(config('resq_dummy.clusters'));
            $sensors = collect(config('resq_dummy.sensors'));
            $monitoringStations = collect(config('resq_dummy.monitoring_stations'));
            $warningStations = collect(config('resq_dummy.warning_stations'));

            return response()->json([
                'clusters' => $this->dummyMapClusters($clusters),
                'sensors' => $this->dummyMapSensors($sensors, $monitoringStations, $clusters),
                'warningStations' => $this->dummyMapWarningStations($warningStations, $sensors, $clusters),
            ]);
        }

        return response()->json([
            'clusters' => $this->mapClusters(),
            'sensors' => $this->mapSensors(),
            'warningStations' => $this->mapWarningStations(),
        ]);
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

    private function mapClusters()
    {
        $provinceCoordinates = $this->provinceCoordinates();

        return GeospatialWorkspace::with(['sensors'])->withCount(['sensors', 'warningStations'])
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

    private function mapSensors()
    {
        $provinceCoordinates = $this->provinceCoordinates();

        return Sensor::with(['workspace', 'monitoringStation', 'warningStation'])
            ->get()
            ->map(function (Sensor $sensor) use ($provinceCoordinates) {
                $workspace = $sensor->workspace;
                $station = $sensor->monitoringStation;
                $fallback = $workspace ? ($provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null]) : ['lat' => null, 'lng' => null];
                $lat = $station?->latitude ?: $workspace?->latitude ?: $fallback['lat'];
                $lng = $station?->longitude ?: $workspace?->longitude ?: $fallback['lng'];
                $thresholdExceeded = $this->thresholdExceeded($sensor->value, $sensor->threshold);
                $isDanger = $this->isDangerStatus($sensor->status)
                    || $this->isDangerStatus($sensor->alert_level)
                    || $thresholdExceeded;
                $status = $thresholdExceeded && ! $this->isDangerStatus($sensor->status)
                    ? 'Awas'
                    : $sensor->status;
                $alertLevel = $thresholdExceeded && ! $this->isDangerStatus($sensor->alert_level)
                    ? 'Awas'
                    : $sensor->alert_level;

                return [
                    'name' => $sensor->sensor_code,
                    'type' => $sensor->type,
                    'parameter' => $sensor->parameter,
                    'value' => $sensor->value,
                    'threshold' => $sensor->threshold,
                    'unit' => $sensor->unit,
                    'alert_level' => $alertLevel,
                    'station' => $station?->station_code,
                    'warning_station' => $sensor->warningStation?->station_code,
                    'province' => $workspace?->province,
                    'status' => $status,
                    'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
                    'threshold_exceeded' => $thresholdExceeded,
                    'is_danger' => $isDanger,
                    'lat' => $lat ? (float) $lat : null,
                    'lng' => $lng ? (float) $lng : null,
                ];
            })
            ->filter(fn ($item) => $item['lat'] !== null && $item['lng'] !== null)
            ->values();
    }

    private function mapWarningStations()
    {
        $provinceCoordinates = $this->provinceCoordinates();

        return WarningStation::with(['workspace', 'sensors'])
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
                            'value' => $sensor->value,
                            'threshold' => $sensor->threshold,
                            'alert_level' => $thresholdExceeded && ! $this->isDangerStatus($sensor->alert_level)
                                ? 'Awas'
                                : $sensor->alert_level,
                            'status' => $thresholdExceeded && ! $this->isDangerStatus($sensor->status)
                                ? 'Awas'
                                : $sensor->status,
                            'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
                        ];
                    })
                    ->values();

                return [
                    'name' => $station->station_code . ' - ' . $station->name,
                    'province' => $workspace?->province,
                    'status' => $station->status,
                    'public_warning_enabled' => $station->public_warning_enabled,
                    'ack_response' => $station->ack_response,
                    'danger_sensors' => $dangerSensors,
                    'is_danger' => $this->isDangerStatus($station->status) || $dangerSensors->isNotEmpty(),
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

    private function dummyMapClusters($clusters)
    {
        return collect($clusters)->map(function ($cluster) {
            $coords = $this->dummyCoordinates($cluster['province'] ?? null, $cluster['id'] ?? null);

            return [
                'name' => $cluster['name'],
                'province' => $cluster['province'],
                'city' => $cluster['city'] ?? '-',
                'hazard' => $cluster['hazard'] ?? '-',
                'status' => $cluster['status'] ?? 'Normal',
                'sensors' => 0,
                'warnings' => ! empty($cluster['warning_station_id']) ? 1 : 0,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ];
        })->values();
    }

    private function dummyMapSensors($sensors, $monitoringStations, $clusters)
    {
        $stations = collect($monitoringStations)->keyBy('id');
        $clusterRows = collect($clusters)->keyBy('id');

        return collect($sensors)->map(function ($sensor) use ($stations, $clusterRows) {
            $station = $stations->get($sensor['monitoring_station_id']);
            $cluster = $clusterRows->get($sensor['cluster_id']);
            $coords = $this->coordinatesFromText($station['coordinate'] ?? null)
                ?? $this->dummyCoordinates($cluster['province'] ?? null, $cluster['id'] ?? null);

            return [
                'name' => $sensor['id'],
                'type' => $sensor['type'] ?? '-',
                'parameter' => $sensor['parameter'] ?? '-',
                'value' => $sensor['value'] ?? '-',
                'threshold' => $sensor['threshold'] ?? '-',
                'unit' => $sensor['unit'] ?? '',
                'alert_level' => $sensor['alert_level'] ?? $sensor['status'] ?? 'Normal',
                'station' => $sensor['monitoring_station_id'] ?? '-',
                'warning_station' => $sensor['warning_station_id'] ?? '-',
                'province' => $cluster['province'] ?? '-',
                'status' => $sensor['status'] ?? 'Normal',
                'last_seen' => $sensor['last_seen'] ?? '-',
                'threshold_exceeded' => $this->thresholdExceeded($sensor['value'] ?? null, $sensor['threshold'] ?? null),
                'is_danger' => $this->isDangerStatus($sensor['status'] ?? null)
                    || $this->thresholdExceeded($sensor['value'] ?? null, $sensor['threshold'] ?? null),
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ];
        })->values();
    }

    private function dummyMapWarningStations($warningStations, $sensors, $clusters)
    {
        $clusterRows = collect($clusters)->keyBy('id');
        $sensorRows = collect($sensors)->groupBy('warning_station_id');

        return collect($warningStations)->map(function ($station) use ($clusterRows, $sensorRows) {
            $cluster = $clusterRows->get($station['cluster_id']);
            $coords = $this->coordinatesFromText($station['coordinate'] ?? null)
                ?? $this->dummyCoordinates($cluster['province'] ?? null, $cluster['id'] ?? null);
            $dangerSensors = $sensorRows
                ->get($station['id'], collect())
                ->filter(fn ($sensor) => $this->isDangerStatus($sensor['status'] ?? null)
                    || $this->thresholdExceeded($sensor['value'] ?? null, $sensor['threshold'] ?? null))
                ->map(fn ($sensor) => [
                    'name' => $sensor['id'],
                    'parameter' => $sensor['parameter'] ?? '-',
                    'value' => $sensor['value'] ?? '-',
                    'threshold' => $sensor['threshold'] ?? '-',
                    'alert_level' => $sensor['alert_level'] ?? $sensor['status'] ?? 'Normal',
                    'status' => $sensor['status'] ?? 'Normal',
                    'last_seen' => $sensor['last_seen'] ?? '-',
                ])
                ->values();

            return [
                'name' => $station['id'] . ' - ' . $station['name'],
                'province' => $cluster['province'] ?? '-',
                'status' => $station['status'] ?? 'Normal',
                'public_warning_enabled' => $station['public_warning_enabled'] ?? false,
                'ack_response' => $station['ack_response'] ?? '-',
                'danger_sensors' => $dangerSensors,
                'is_danger' => $this->isDangerStatus($station['status'] ?? null) || $dangerSensors->isNotEmpty(),
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ];
        })->values();
    }

    private function dummyCoordinates(?string $province, ?string $id): array
    {
        $coordinates = [
            'Sumatera Barat' => ['lat' => -0.9200, 'lng' => 100.3600],
            'DKI Jakarta' => ['lat' => -6.1290, 'lng' => 106.8100],
        ];

        return $coordinates[$province] ?? ['lat' => -2.6, 'lng' => 118.0];
    }

    private function coordinatesFromText(?string $coordinate): ?array
    {
        if (empty($coordinate)) {
            return null;
        }

        $parts = array_map('trim', explode(',', $coordinate));

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        return [
            'lat' => (float) $parts[0],
            'lng' => (float) $parts[1],
        ];
    }
}
