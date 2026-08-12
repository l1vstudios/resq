<?php

namespace App\Http\Controllers;

use App\Models\CorridorMonitoring;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\WarningStation;
use App\Services\AuthorizationService;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ClientPlatformOperationsController extends Controller
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

    public function index(Request $request): RedirectResponse
    {
        $this->authorizeClient($request);

        $query = Project::query()->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $query);
        $project = $query->firstOrFail();

        $requestedPerspective = (string) $request->query('tab', '');

        if (in_array($requestedPerspective, ['integrity', 'administrative'], true)) {
            return redirect()->route('client-operations.projects.stations', [$project, 'tab' => $requestedPerspective]);
        }

        return redirect()->route('client-operations.projects.show', $project);
    }

    public function project(Request $request, Project $project): View
    {
        $this->authorizeClientProject($request, $project);

        $project->load(['corridors', 'workspaces', 'monitoringStations.corridor', 'monitoringStations.sensors', 'warningStations']);
        $runtime = $this->runtime->projectRuntime($project);
        $stationRows = collect($runtime['stations']);

        return view('modules.platform-operations.project', [
            'project' => $project,
            'runtime' => $runtime,
            'corridors' => $project->corridors
                ->map(fn (CorridorMonitoring $corridor) => $this->corridorSummary($corridor, $stationRows))
                ->values(),
            'mapLines' => $this->corridorMapLines($project->corridors),
            'mapStations' => $this->operationalMapPoints($project->monitoringStations, $project->warningStations),
            'permissions' => $this->clientPermissions($request, $project),
            'operationsTitle' => 'Client Operations',
            'showNationalLink' => false,
        ]);
    }

    public function corridor(Request $request, Project $project, CorridorMonitoring $corridor): View
    {
        $this->authorizeClientProject($request, $project);
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
            ->map(fn (array $row) => [
                ...$row,
                'station_url' => route('client-operations.stations.show', $row['station']['id']),
            ])
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
            'permissions' => $this->clientPermissions($request, $project),
            'operationsTitle' => 'Client Operations',
            'projectRoute' => route('client-operations.projects.show', $project),
            'reportUrl' => route('client-operations.reporting.index', [
                'target_type' => 'corridor',
                'target_id' => $corridor->id,
                'project_id' => $project->id,
            ]),
        ]);
    }

    public function stations(Request $request, Project $project): View
    {
        $this->authorizeClientProject($request, $project);

        $perspective = $this->activeListPerspective((string) $request->query('tab', 'integrity'));
        $runtime = $this->runtime->projectRuntime($project);
        $rows = collect($runtime['stations'])
            ->filter(fn (array $row) => $this->matchesFilter($row, $perspective, $request->query('status')))
            ->map(fn (array $row) => [
                ...$row,
                'station_url' => route('client-operations.stations.show', [$row['station']['id'], 'tab' => $perspective]),
            ])
            ->values();

        return view('modules.client-operations.station-list', [
            'project' => $project,
            'stations' => $rows,
            'perspective' => $perspective,
            'activeFilter' => $request->query('status'),
            'operationsTitle' => 'Client Operations',
        ]);
    }

    public function station(Request $request, MonitoringStation $station): View
    {
        $tab = $this->activeTab((string) $request->query('tab', 'state'));
        $station->load(['project', 'workspace.project', 'corridor', 'sensors']);
        $this->authorizeClientProject($request, $station->project);

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
            'permissions' => $this->clientPermissions($request, $station->project),
            'operationsTitle' => 'Client Operations',
            'stationRouteName' => 'client-operations.stations.show',
            'showFutureTabs' => true,
            'functionConfigUrl' => route('client-operations.function-configuration.stations.show', $station),
            'functionConfigEnabled' => true,
            'reportingUrl' => route('client-operations.reporting.index', [
                'target_type' => 'station',
                'target_id' => $station->id,
                'project_id' => $station->project_id,
            ]),
            'reportingEnabled' => true,
            'showReportingFutureTab' => true,
        ]);
    }

    private function authorizeClient(Request $request): void
    {
        abort_unless($request->user()?->isClientUser(), 403);
    }

    private function authorizeClientProject(Request $request, Project $project): void
    {
        $this->authorizeClient($request);
        abort_unless($this->authorization->canAccessProject($request->user(), $project), 403);
    }

    private function clientPermissions(Request $request, Project $project): array
    {
        $canAccessProject = $this->authorization->canAccessProject($request->user(), $project);

        return [
            'state' => $canAccessProject,
            'integrity' => $canAccessProject,
            'administrative' => $canAccessProject,
            'reporting' => false,
        ];
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
            'url' => route('client-operations.corridors.show', [$corridor->project_id, $corridor]),
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
                'url' => route('client-operations.stations.show', $station),
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
            'url' => route('client-operations.stations.show', $station),
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

    private function worstState(Collection $states): string
    {
        return $states
            ->filter()
            ->map(fn ($state) => strtoupper((string) $state))
            ->sortByDesc(fn (string $state) => self::HAZARD_RANK[$state] ?? 0)
            ->first() ?: 'NORMAL';
    }

    private function matchesFilter(array $row, string $perspective, ?string $filter): bool
    {
        if (! $filter) {
            return true;
        }

        if ($perspective === 'administrative') {
            return $this->matchesAdministrativeFilter($row, $filter);
        }

        return strcasecmp($row['integrity']['overall'] ?? '', $filter) === 0;
    }

    private function matchesAdministrativeFilter(array $row, ?string $filter): bool
    {
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

    private function activeListPerspective(string $perspective): string
    {
        return in_array($perspective, ['integrity', 'administrative'], true) ? $perspective : 'integrity';
    }

    private function activeTab(string $tab): string
    {
        return in_array($tab, ['state', 'integrity', 'administrative'], true) ? $tab : 'state';
    }
}
