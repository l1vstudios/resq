<?php

namespace App\Http\Controllers;

use App\Models\CorridorMonitoring;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\WarningStation;
use App\Services\AuthorizationService;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PlatformOperationsController extends Controller
{
    private const HAZARD_RANK = [
        'NORMAL' => 0,
        'HEALTHY' => 0,
        'WASPADA' => 1,
        'WARNING' => 1,
        'SIAGA' => 2,
        'AWAS' => 3,
        'CRITICAL' => 3,
        'DANGER' => 3,
        'OFFLINE' => 4,
    ];

    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly SentinelRuntimeReadService $runtime
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizePerspective($request, 'state');

        $projects = $this->accessibleProjects($request)
            ->load(['corridors', 'workspaces', 'monitoringStations.sensors', 'warningStations']);

        return view('modules.platform-operations.national', [
            'projectSummaries' => $projects->map(fn (Project $project) => $this->projectSummary($project))->values(),
            'mapProjects' => $this->nationalMapPoints($projects),
            'mapLines' => $this->corridorMapLines($projects->flatMap(fn (Project $project) => $project->corridors)),
            'flowLinks' => $this->operationFlowLinks($projects),
            'permissions' => $this->operationPermissions($request),
        ]);
    }

    public function project(Request $request, Project $project): View
    {
        $this->authorizePerspective($request, 'state');
        $this->authorizeProject($request, $project);

        $project->load(['corridors', 'workspaces', 'monitoringStations.corridor', 'monitoringStations.sensors', 'warningStations']);
        $runtime = $this->runtime->projectRuntime($project);

        return view('modules.platform-operations.project', [
            'project' => $project,
            'runtime' => $runtime,
            'corridors' => $project->corridors->map(fn (CorridorMonitoring $corridor) => $this->corridorSummary($corridor, collect($runtime['stations'])))->values(),
            'mapLines' => $this->corridorMapLines($project->corridors),
            'mapStations' => $this->operationalMapPoints($project->monitoringStations, $project->warningStations),
            'permissions' => $this->operationPermissions($request),
        ]);
    }

    public function corridor(Request $request, Project $project, CorridorMonitoring $corridor): View
    {
        $this->authorizePerspective($request, 'state');
        $this->authorizeProject($request, $project);
        abort_if((int) $corridor->project_id !== (int) $project->id, 403);

        $corridor->load(['workspace', 'project']);
        $stations = MonitoringStation::with(['project', 'workspace.project', 'corridor', 'sensors'])
            ->where('project_id', $project->id)
            ->where('corridor_id', $corridor->id)
            ->orderBy('station_code')
            ->get();
        $warningStations = WarningStation::query()
            ->where('project_id', $project->id)
            ->where(function ($query) use ($stations) {
                $query
                    ->whereIn('monitoring_station_id', $stations->pluck('id')->filter()->all())
                    ->orWhereNull('monitoring_station_id');
            })
            ->orderBy('station_code')
            ->get();
        $runtime = $this->runtime->projectRuntime($project);
        $stationRows = collect($runtime['stations'])
            ->filter(fn (array $row) => (int) ($row['station']['corridor_id'] ?? 0) === (int) $corridor->id)
            ->values();

        return view('modules.platform-operations.corridor', [
            'project' => $project,
            'corridor' => $corridor,
            'stations' => $stations,
            'stationRows' => $stationRows,
            'mapLines' => $this->corridorMapLines(collect([$corridor])),
            'mapStations' => $this->operationalMapPoints($stations, $warningStations),
            'currentPreview' => $this->currentPreview($stations),
            'seriesPreview' => $this->seriesPreview($stations),
            'permissions' => $this->operationPermissions($request),
        ]);
    }

    public function station(Request $request, MonitoringStation $station): View
    {
        $tab = $this->activeTab((string) $request->query('tab', 'state'));
        $this->authorizePerspective($request, $tab);
        $this->authorizeStation($request, $station);

        $station->load(['project', 'workspace.project', 'corridor', 'sensors']);

        return view('modules.platform-operations.station', [
            'station' => $station,
            'activeTab' => $tab,
            'runtime' => $this->runtime->stationRuntime($station),
            'latest' => $this->runtime->latestReadings($station),
            'timeSeries' => $this->runtime->timeSeries($station, ['limit' => 120]),
            'stationContextStations' => MonitoringStation::with(['corridor'])
                ->where('project_id', $station->project_id)
                ->orderBy('corridor_id')
                ->orderBy('station_code')
                ->get(),
            'stationContextCorridors' => CorridorMonitoring::query()
                ->where('project_id', $station->project_id)
                ->orderBy('corridor_code')
                ->get(),
            'permissions' => $this->operationPermissions($request),
        ]);
    }

    public function integrity(Request $request): View
    {
        $this->authorizePerspective($request, 'integrity');

        return view('modules.platform-operations.integrity', [
            'stations' => $this->stationLandingRows($request)->filter(function (array $row) use ($request) {
                $filter = $request->query('status');

                return ! $filter || strcasecmp($row['integrity']['overall'] ?? '', (string) $filter) === 0;
            })->values(),
            'activeFilter' => $request->query('status'),
            'permissions' => $this->operationPermissions($request),
        ]);
    }

    public function administrative(Request $request): View
    {
        $this->authorizePerspective($request, 'administrative');

        return view('modules.platform-operations.administrative', [
            'stations' => $this->stationLandingRows($request)->filter(function (array $row) use ($request) {
                return $this->matchesAdministrativeFilter($row, $request->query('status'));
            })->values(),
            'activeFilter' => $request->query('status'),
            'permissions' => $this->operationPermissions($request),
        ]);
    }

    private function stationLandingRows(Request $request): Collection
    {
        return $this->accessibleProjects($request)
            ->flatMap(fn (Project $project) => collect($this->runtime->projectRuntime($project)['stations']));
    }

    private function accessibleProjects(Request $request): Collection
    {
        $query = Project::query()->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $query);

        return $query->get();
    }

    private function operationFlowLinks(Collection $projects): array
    {
        /** @var Project|null $project */
        $project = $projects->first();
        $corridor = $project?->corridors->first();
        $station = $project?->monitoringStations->first();

        return [
            'national' => route('platform-operations.index'),
            'select_project' => '#ops-project-list',
            'project_context' => $project ? route('platform-operations.projects.show', $project) : '#ops-project-list',
            'corridor' => $project && $corridor ? route('platform-operations.corridors.show', [$project, $corridor]) : '#ops-project-list',
            'station_ui' => $station ? route('platform-operations.stations.show', $station) : '#ops-project-list',
        ];
    }

    private function projectSummary(Project $project): array
    {
        $stations = $project->monitoringStations;
        $lastUpdate = $stations
            ->flatMap(fn (MonitoringStation $station) => $station->sensors->pluck('last_seen_at'))
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'id' => $project->id,
            'project_code' => $project->project_code,
            'name' => $project->name,
            'area' => $this->projectArea($project),
            'station_count' => $stations->count(),
            'warning_station_count' => $project->warningStations->count(),
            'state' => $this->projectState($stations),
            'last_update' => optional($lastUpdate)->toISOString(),
        ];
    }

    private function projectMapPoint(Project $project): ?array
    {
        $workspace = $project->workspaces
            ->first(fn ($workspace) => $workspace->latitude !== null && $workspace->longitude !== null);

        if (! $workspace) {
            return null;
        }

        return [
            'project_code' => $project->project_code,
            'name' => $project->name,
            'lat' => (float) $workspace->latitude,
            'lng' => (float) $workspace->longitude,
            'state' => $this->projectState($project->monitoringStations),
            'url' => route('platform-operations.projects.show', $project),
        ];
    }

    private function nationalMapPoints(Collection $projects): Collection
    {
        $projectPoints = $projects
            ->map(fn (Project $project) => $this->projectMapPoint($project))
            ->filter()
            ->values();

        $assetPoints = $this->operationalMapPoints(
            $projects->flatMap(fn (Project $project) => $project->monitoringStations),
            $projects->flatMap(fn (Project $project) => $project->warningStations)
        );

        return $projectPoints
            ->concat($assetPoints)
            ->values();
    }

    private function corridorSummary(CorridorMonitoring $corridor, Collection $stationRows): array
    {
        $stations = $stationRows
            ->filter(fn (array $row) => (int) ($row['station']['corridor_id'] ?? 0) === (int) $corridor->id)
            ->values();

        return [
            'id' => $corridor->id,
            'corridor_code' => $corridor->corridor_code,
            'name' => $corridor->name,
            'status' => $corridor->status,
            'station_count' => $stations->count(),
            'hazard_state' => $this->worstState($stations->pluck('hazard.state')),
            'url' => route('platform-operations.corridors.show', [$corridor->project_id, $corridor]),
        ];
    }

    private function operationalMapPoints(Collection $monitoringStations, Collection $warningStations): Collection
    {
        $monitoringPoints = $monitoringStations
            ->filter(fn (MonitoringStation $station) => $station->latitude !== null && $station->longitude !== null)
            ->map(fn (MonitoringStation $station) => [
                'type' => 'monitoring_station',
                'station_code' => $station->station_code,
                'label' => $station->station_code,
                'name' => $station->name,
                'asset_label' => 'Monitoring Station',
                'lat' => (float) $station->latitude,
                'lng' => (float) $station->longitude,
                'status' => $station->status,
                'url' => route('platform-operations.stations.show', $station),
            ])
            ->values();

        $sensorPoints = $monitoringStations
            ->flatMap(fn (MonitoringStation $station) => $station->sensors->values()->map(
                fn (Sensor $sensor, int $index) => $this->sensorMapPoint($station, $sensor, $index)
            ))
            ->filter()
            ->values();

        $warningPoints = $warningStations
            ->filter(fn (WarningStation $station) => $station->latitude !== null && $station->longitude !== null)
            ->map(fn (WarningStation $station) => [
                'type' => 'warning_station',
                'station_code' => $station->station_code,
                'label' => $station->station_code,
                'name' => $station->name,
                'asset_label' => 'Warning Station',
                'lat' => (float) $station->latitude,
                'lng' => (float) $station->longitude,
                'status' => $station->status ?? $station->controller_status,
            ])
            ->values();

        return $monitoringPoints
            ->concat($sensorPoints)
            ->concat($warningPoints)
            ->values();
    }

    private function sensorMapPoint(MonitoringStation $station, Sensor $sensor, int $index): ?array
    {
        if ($station->latitude === null || $station->longitude === null) {
            return null;
        }

        $angle = deg2rad(($index % 8) * 45);
        $distance = 0.0010 + (floor($index / 8) * 0.0005);

        return [
            'type' => 'sensor',
            'sensor_code' => $sensor->sensor_code,
            'label' => $sensor->sensor_code,
            'name' => $sensor->parameter ?: $sensor->type,
            'asset_label' => $station->station_code.' / Sensor',
            'lat' => round((float) $station->latitude + (cos($angle) * $distance), 6),
            'lng' => round((float) $station->longitude + (sin($angle) * $distance), 6),
            'status' => $sensor->status,
            'alert_level' => $sensor->alert_level,
            'url' => route('platform-operations.stations.show', $station),
        ];
    }

    private function corridorMapLines(Collection $corridors): Collection
    {
        return $corridors
            ->filter(fn (CorridorMonitoring $corridor) => filled($corridor->path_coordinates))
            ->map(fn (CorridorMonitoring $corridor) => [
                'type' => 'corridor',
                'code' => $corridor->corridor_code,
                'label' => $corridor->corridor_code,
                'name' => $corridor->name,
                'status' => $corridor->status,
                'path' => $corridor->path_coordinates ?? [],
            ])
            ->values();
    }

    private function currentPreview(Collection $stations): Collection
    {
        return $stations
            ->flatMap(fn (MonitoringStation $station) => collect($this->runtime->latestReadings($station)['readings'])->map(fn (array $reading) => [
                ...$reading,
                'station_code' => $station->station_code,
                'station_name' => $station->name,
            ]))
            ->take(80)
            ->values();
    }

    private function seriesPreview(Collection $stations): Collection
    {
        return $stations
            ->flatMap(function (MonitoringStation $station) {
                $sensor = $station->sensors->first();

                if (! $sensor) {
                    return collect();
                }

                $series = $this->runtime->timeSeries($station, [
                    'sensor_id' => $sensor->id,
                    'limit' => 12,
                ]);
                $readings = collect($series['readings']);
                $parameterPoints = $readings
                    ->flatMap(fn (array $row) => collect($row['parameter_values'] ?? [])->map(fn (array $item) => [
                        'parameter' => $item['parameter'] ?? $item['label'] ?? $row['parameter'],
                        'timestamp' => $row['timestamp'],
                        'value' => $item['value'] ?? null,
                        'unit' => $item['unit'] ?? $row['unit'],
                    ]));

                if ($parameterPoints->isEmpty()) {
                    $parameterPoints = $readings->map(fn (array $row) => [
                        'parameter' => $row['parameter'],
                        'timestamp' => $row['timestamp'],
                        'value' => $row['value'],
                        'unit' => $row['unit'],
                    ]);
                }

                return $parameterPoints
                    ->groupBy('parameter')
                    ->map(fn (Collection $points, string $parameter) => [
                        'station_code' => $station->station_code,
                        'sensor_code' => $sensor->sensor_code,
                        'parameter' => $parameter,
                        'unit' => $points->pluck('unit')->filter()->first(),
                        'points' => $points->take(12)->values(),
                    ])
                    ->values();
            })
            ->filter()
            ->take(24)
            ->values();
    }

    private function projectArea(Project $project): string
    {
        return $project->workspaces
            ->map(fn ($workspace) => trim(collect([$workspace->province, $workspace->city])->filter()->implode(' / ')))
            ->filter()
            ->unique()
            ->take(3)
            ->implode(', ') ?: '-';
    }

    private function projectState(Collection $stations): string
    {
        return $this->worstState($stations->flatMap(fn (MonitoringStation $station) => $station->sensors->flatMap(fn (Sensor $sensor) => [
            $sensor->alert_level,
            $sensor->status,
        ])));
    }

    private function worstState(Collection $states): string
    {
        return $states
            ->filter()
            ->map(fn ($state) => strtoupper((string) $state))
            ->sortByDesc(fn (string $state) => self::HAZARD_RANK[$state] ?? 0)
            ->first() ?: 'NORMAL';
    }

    private function matchesAdministrativeFilter(array $row, ?string $filter): bool
    {
        if (! $filter) {
            return true;
        }

        $admin = $row['administrative'] ?? [];

        return match ($filter) {
            'Active' => strcasecmp($admin['service_status'] ?? '', 'Active') === 0,
            'Inactive' => in_array(strtolower((string) ($admin['service_status'] ?? '')), ['inactive', 'suspended', 'expired'], true),
            'Expiring Soon' => $this->isExpiringSoon($admin['service_period']['end'] ?? null),
            'Attention' => ! empty($admin['administrative_attention']),
            default => true,
        };
    }

    private function isExpiringSoon(?string $date): bool
    {
        if (! $date) {
            return false;
        }

        $end = Carbon::parse($date);

        return $end->betweenIncluded(now(), now()->addDays(45));
    }

    private function activeTab(string $tab): string
    {
        return in_array($tab, ['state', 'integrity', 'administrative'], true) ? $tab : 'state';
    }

    private function authorizePerspective(Request $request, string $perspective): void
    {
        $user = $request->user();
        $allowed = match ($perspective) {
            'integrity' => $this->authorization->canAccessOperationalIntegrity($user),
            'administrative' => $this->authorization->canAccessAdministrativeMonitoring($user),
            default => $this->authorization->canAccessOperationalState($user),
        };

        abort_unless($allowed, 403);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $project), 403);
    }

    private function authorizeStation(Request $request, MonitoringStation $station): void
    {
        abort_unless($this->authorization->canAccessMonitoringStation($request->user(), $station), 403);
    }

    private function operationPermissions(Request $request): array
    {
        $user = $request->user();

        return [
            'state' => $this->authorization->canAccessOperationalState($user),
            'integrity' => $this->authorization->canAccessOperationalIntegrity($user),
            'administrative' => $this->authorization->canAccessAdministrativeMonitoring($user),
            'reporting' => $this->authorization->canAccessReporting($user),
        ];
    }
}
