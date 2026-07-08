<?php

namespace App\Http\Controllers;

use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Province;
use App\Models\Sensor;
use App\Models\WarningStation;
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

        return GeospatialWorkspace::withCount(['sensors', 'warningStations'])
            ->get()
            ->map(function (GeospatialWorkspace $workspace) use ($provinceCoordinates) {
                $fallback = $provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null];
                $lat = $workspace->latitude ?: $fallback['lat'];
                $lng = $workspace->longitude ?: $fallback['lng'];

                return [
                    'name' => $workspace->name,
                    'province' => $workspace->province,
                    'city' => $workspace->city,
                    'hazard' => $workspace->hazard,
                    'status' => $workspace->status,
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

        return Sensor::with(['workspace', 'monitoringStation'])
            ->get()
            ->map(function (Sensor $sensor) use ($provinceCoordinates) {
                $workspace = $sensor->workspace;
                $station = $sensor->monitoringStation;
                $fallback = $workspace ? ($provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null]) : ['lat' => null, 'lng' => null];
                $lat = $station?->latitude ?: $workspace?->latitude ?: $fallback['lat'];
                $lng = $station?->longitude ?: $workspace?->longitude ?: $fallback['lng'];

                return [
                    'name' => $sensor->sensor_code,
                    'type' => $sensor->type,
                    'station' => $station?->station_code,
                    'province' => $workspace?->province,
                    'status' => $sensor->status,
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

        return WarningStation::with('workspace')
            ->get()
            ->map(function (WarningStation $station) use ($provinceCoordinates) {
                $workspace = $station->workspace;
                $fallback = $workspace ? ($provinceCoordinates[$workspace->province] ?? ['lat' => null, 'lng' => null]) : ['lat' => null, 'lng' => null];
                $lat = $station->latitude ?: $workspace?->latitude ?: $fallback['lat'];
                $lng = $station->longitude ?: $workspace?->longitude ?: $fallback['lng'];

                return [
                    'name' => $station->station_code . ' - ' . $station->name,
                    'province' => $workspace?->province,
                    'status' => $station->status,
                    'lat' => $lat ? (float) $lat : null,
                    'lng' => $lng ? (float) $lng : null,
                ];
            })
            ->filter(fn ($item) => $item['lat'] !== null && $item['lng'] !== null)
            ->values();
    }
}
