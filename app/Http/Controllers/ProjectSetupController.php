<?php

namespace App\Http\Controllers;

use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\MqttConfiguration;
use App\Models\Project;
use App\Models\Province;
use App\Models\ReferencePoint;
use App\Models\ReferenceRoute;
use App\Models\ResponsePlan;
use App\Models\CanonicalParameter;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\SpatialInformationLayer;
use App\Models\StationSpatialReference;
use App\Models\TelemetryReading;
use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use App\Models\WarningStationTelemetryConfig;
use App\Services\CanonicalMappingService;
use App\Services\MonitoringStationDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectSetupController extends Controller
{
    public function __construct(
        private readonly CanonicalMappingService $canonicalMapping,
        private readonly \App\Services\AuthorizationService $authorizationService
    ) {
    }

    public function index(): View
    {
        return view('modules.projects.index', $this->viewData());
    }

    public function monitoring(): View
    {
        return view('modules.projects.monitoring', $this->viewData());
    }

    public function spatialResources(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($request, $project);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'name' => $project->name,
            ],
            'resources' => $this->spatialResourcesForProjects(collect([$project])),
        ]);
    }

    public function projectCorridor(Request $request, Project $project, CorridorMonitoring $corridor): JsonResponse
    {
        $this->authorizeProjectAccess($request, $project);
        abort_if((int) $corridor->project_id !== (int) $project->id, 403);

        return response()->json([
            'corridor' => $this->corridorRow($corridor->load(['workspace', 'referenceRoute'])),
        ]);
    }

    public function monitoringStationDomain(
        Request $request,
        MonitoringStation $station,
        MonitoringStationDomainService $stationDomain
    ): JsonResponse {
        abort_unless($this->authorizationService->canAccessMonitoringStation($request->user(), $station), 403);

        return response()->json($stationDomain->stationDomain($station));
    }

    public function startMonitoring(Request $request)
    {
        $data = $request->validate(['project_id' => 'required|exists:resq_projects,id']);
        $project = Project::findOrFail($data['project_id']);
        $loggers = $this->projectDataLoggers($project)
            ->map(fn (DataLogger $dataLogger) => $this->setMonitoringRuntime($dataLogger, true))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'message' => count($loggers)
                ? 'Monitoring project berhasil dimulai.'
                : 'Monitoring project dimulai, tetapi belum ada logger pada project ini.',
            'loggers' => $loggers,
        ]);
    }

    public function stopMonitoring(Request $request)
    {
        $data = $request->validate(['project_id' => 'required|exists:resq_projects,id']);
        $project = Project::findOrFail($data['project_id']);
        $loggers = $this->projectDataLoggers($project)
            ->map(fn (DataLogger $dataLogger) => $this->setMonitoringRuntime($dataLogger, false))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'message' => 'Monitoring project berhasil dihentikan.',
            'loggers' => $loggers,
        ]);
    }

    public function liveMonitoring(Request $request)
    {
        $data = $request->validate(['project_id' => 'nullable|exists:resq_projects,id']);

        if (empty($data['project_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Project tidak dipilih.',
                'summary' => ['loggers' => 0, 'online_loggers' => 0, 'sensors' => 0, 'fresh_sensors' => 0],
                'sensors' => [],
                'generated_at' => now()->toISOString(),
            ]);
        }

        $project = Project::findOrFail($data['project_id']);
        $workspaces = $project->workspaces ?? collect();

        $projectSensors = collect();
        $loggerIds = [];
        $loggerById = collect();
        $sensorLoggerFallbacks = [];

        foreach ($workspaces as $workspace) {
            foreach ($workspace->monitoringStations ?? collect() as $station) {
                $stationLoggers = collect($station->dataLoggers ?? []);
                foreach ($station->dataLoggers ?? collect() as $dataLogger) {
                    $loggerIds[] = $dataLogger->id;
                    $loggerById->put($dataLogger->id, $dataLogger);
                }

                foreach ($station->sensors ?? collect() as $sensor) {
                    $projectSensors->push($sensor);
                    $fallbackLogger = $sensor->dataLogger ?? $stationLoggers->first();
                    if ($fallbackLogger) {
                        $sensorLoggerFallbacks[$sensor->id] = $fallbackLogger->id;
                        $loggerIds[] = $fallbackLogger->id;
                        $loggerById->put($fallbackLogger->id, $fallbackLogger);
                    }
                }
            }
        }

        $loggerIds = array_values(array_unique($loggerIds));
        $sensorIds = $projectSensors->pluck('id')->all();
        $onlineLoggerIds = $this->onlineLoggerIds($loggerIds);

        $latestReadings = TelemetryReading::with(['sensor.monitoringStation', 'dataLogger'])
            ->when(! empty($sensorIds), fn ($q) => $q->whereIn('sensor_id', $sensorIds))
            ->latest('received_at')
            ->latest()
            ->limit(500)
            ->get()
            ->unique('sensor_id')
            ->keyBy('sensor_id');

        $freshWindowSeconds = 30;
        $freshCount = 0;
        $sensors = [];

        foreach ($projectSensors as $sensor) {
            $reading = $latestReadings->get($sensor->id);
            $receivedAt = $reading?->received_at;

            $isFresh = $receivedAt
                ? $receivedAt->greaterThanOrEqualTo(now()->subSeconds($freshWindowSeconds))
                : false;

            $loggerId = $reading?->data_logger_id
                ?? $sensor->data_logger_id
                ?? ($sensorLoggerFallbacks[$sensor->id] ?? null);

            if ($isFresh && $loggerId && ! in_array($loggerId, $onlineLoggerIds)) {
                $onlineLoggerIds[] = $loggerId;
            }

            if ($isFresh) {
                $freshCount++;
            }

            $parameterValues = $this->liveParameterValues($sensor, $reading, $sensor->value);

            $sensors[] = [
                'id' => $sensor->id,
                'logger_code' => $reading?->dataLogger?->logger_code
                    ?? $sensor->dataLogger?->logger_code
                    ?? $loggerById->get($loggerId)?->logger_code
                    ?? '-',
                'station' => $sensor->monitoringStation?->name ?? '-',
                'sensor_code' => $sensor->sensor_code,
                'sensor_label' => $sensor->parameter,
                'sensor_type' => $sensor->type,
                'value' => $this->liveSensorValue($sensor, $reading?->value ?? $sensor->value, $parameterValues),
                'status' => $reading?->status ?? $sensor->status,
                'parameter_values' => $parameterValues,
                'received_at' => optional($receivedAt)->toISOString(),
                'fresh' => $isFresh,
                'online' => $loggerId ? in_array($loggerId, $onlineLoggerIds) : false,
            ];
        }

        return response()->json([
            'ok' => true,
            'summary' => [
                'loggers' => count($loggerIds),
                'online_loggers' => count(array_unique($onlineLoggerIds)),
                'sensors' => count($sensorIds),
                'fresh_sensors' => $freshCount,
            ],
            'sensors' => $sensors,
            'generated_at' => now()->toISOString(),
        ]);
    }

    private function projectDataLoggers(Project $project)
    {
        $loggers = collect();

        foreach ($project->workspaces ?? collect() as $workspace) {
            foreach ($workspace->monitoringStations ?? collect() as $station) {
                foreach ($station->dataLoggers ?? collect() as $dataLogger) {
                    $loggers->push($dataLogger);
                }

                foreach ($station->sensors ?? collect() as $sensor) {
                    if ($sensor->dataLogger) {
                        $loggers->push($sensor->dataLogger);
                    }
                }
            }
        }

        return $loggers->unique('id')->values();
    }

    private function onlineLoggerIds(array $loggerIds): array
    {
        if (empty($loggerIds) || ! Schema::hasTable('connectivity_configs')) {
            return [];
        }

        return ConnectivityConfig::whereIn('data_logger_id', $loggerIds)
            ->where('protocol', 'Modbus RTU')
            ->get()
            ->filter(function (ConnectivityConfig $connectivity) {
                $runtime = $connectivity->runtime_state ?? [];
                $lastSeenAt = $runtime['last_seen_at'] ?? null;

                return $lastSeenAt
                    && \Illuminate\Support\Carbon::parse($lastSeenAt)->greaterThanOrEqualTo(now()->subSeconds(30));
            })
            ->pluck('data_logger_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function setMonitoringRuntime(DataLogger $dataLogger, bool $enabled): array
    {
        $connectivity = ConnectivityConfig::firstOrCreate(
            ['connectivity_code' => 'REDNODE-' . $dataLogger->logger_code],
            [
                'data_logger_id' => $dataLogger->id,
                'communication_type' => 'RS485',
                'protocol' => 'Modbus RTU',
                'host_or_endpoint' => env('REDNODE_SERIAL_PORT', '/dev/ttyAS2'),
                'gateway_id' => $dataLogger->logger_code,
                'connectivity_status' => 'Offline',
            ]
        );

        $runtime = $connectivity->runtime_state ?? [];
        $connectivity->update([
            'data_logger_id' => $dataLogger->id,
            'connectivity_status' => $enabled ? 'Online' : 'Offline',
            'runtime_state' => array_merge($runtime, [
                'monitoring_enabled' => $enabled,
                'last_action' => $enabled ? 'start' : 'stop',
                'last_commanded_at' => now()->toISOString(),
                'last_error' => $enabled ? null : ($runtime['last_error'] ?? null),
            ]),
        ]);

        return [
            'logger_code' => $dataLogger->logger_code,
            'ok' => true,
            'message' => $enabled
                ? 'Gateway akan start polling saat config berikutnya diambil.'
                : 'Gateway akan stop polling saat config berikutnya diambil.',
            'terminal_log' => [
                $enabled
                    ? 'Runtime logger diset Online dari web.'
                    : 'Runtime logger diset Offline dari web.',
            ],
        ];
    }

    private function liveSensorValue(Sensor $sensor, mixed $value, array $parameterValues = []): mixed
    {
        if ($parameterValues !== []) {
            return collect($parameterValues)
                ->pluck('value_text')
                ->filter()
                ->implode(', ') ?: $this->valueWithUnit($parameterValues[0]['value'] ?? '-', $parameterValues[0]['unit'] ?? $sensor->unit);
        }

        $mapped = $this->canonicalMapping->mappedParameterValue($sensor, $value);

        return $mapped['value_text'] ?? $this->valueWithUnit($value, $sensor->unit);
    }

    private function liveParameterValues(Sensor $sensor, ?TelemetryReading $reading, mixed $fallback): array
    {
        $existing = collect($reading?->parameter_values ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($sensor) {
                $parameter = $item['parameter'] ?? $item['canonical_field'] ?? $item['field'] ?? $item['source_parameter'] ?? $sensor->parameter;
                $unit = $item['unit'] ?? $item['canonical_unit'] ?? $sensor->unit;
                $value = $item['value'] ?? $item['numeric_value'] ?? null;

                return [
                    ...$item,
                    'label' => $item['label'] ?? $parameter,
                    'parameter' => $parameter,
                    'value' => $value,
                    'value_text' => $item['value_text'] ?? $this->valueWithUnit($value, $unit),
                    'unit' => $unit,
                ];
            })
            ->values()
            ->all();

        if ($existing !== []) {
            return $existing;
        }

        $sourceValue = $reading?->raw_value ?? $fallback;
        $mapped = $this->canonicalMapping->mappedParameterValue($sensor, $sourceValue);

        return $mapped ? [$mapped] : [];
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

    public function storeProject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'project_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $existing = Project::where('project_code', $data['project_code'])->first();
        $this->authorizeProjectConfigurationMutation($request, $existing, $existing ? 'edit' : 'create');

        Project::updateOrCreate(['project_code' => $data['project_code']], $data);

        return back()->with('message', 'Project berhasil disimpan.');
    }

    public function storeWorkspace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'hazard' => ['nullable', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'beneficiaries' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'string', 'max:50'],
            'basemap_provider' => ['nullable', 'string', 'max:255'],
            'basemap_tile_url' => ['nullable', 'string', 'max:500'],
            'default_zoom' => ['nullable', 'integer', 'min:1', 'max:18'],
            'map_bounds' => ['nullable'],
        ]);
        $data = $this->applyProvinceCoordinates($data);
        $data['basemap_provider'] = $data['basemap_provider'] ?? 'OpenStreetMap';
        $data['default_zoom'] = $data['default_zoom'] ?? 5;
        $data['map_bounds'] = $this->jsonPayload($data['map_bounds'] ?? null, 'map_bounds');

        $project = Project::findOrFail($data['project_id']);
        $existing = GeospatialWorkspace::where('workspace_code', $data['workspace_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeSpatialMutation($request, $project, $existing ? 'edit' : 'create');

        GeospatialWorkspace::updateOrCreate(['workspace_code' => $data['workspace_code']], $data);

        return back()->with('message', 'Geospatial workspace berhasil disimpan.');
    }

    public function storeInformationLayer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
            'layer_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'layer_type' => ['required', 'string', 'max:100'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'layer_payload' => ['nullable'],
            'style_color' => ['nullable', 'string', 'max:20'],
            'visible_by_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->assertNullableWorkspaceInProject($data['workspace_id'] ?? null, $project->id);
        $existing = SpatialInformationLayer::where('layer_code', $data['layer_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeSpatialMutation($request, $project, $existing ? 'edit' : 'create');

        $data['visible_by_default'] = $request->boolean('visible_by_default');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['layer_payload'] = $this->jsonPayload($data['layer_payload'] ?? null, 'layer_payload');

        SpatialInformationLayer::updateOrCreate(['layer_code' => $data['layer_code']], $data);

        return back()->with('message', 'Information layer berhasil disimpan.');
    }

    public function storeReferenceRoute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
            'route_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'route_type' => ['required', 'string', 'max:100'],
            'path_coordinates' => ['nullable'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->assertNullableWorkspaceInProject($data['workspace_id'] ?? null, $project->id);
        $existing = ReferenceRoute::where('route_code', $data['route_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeSpatialMutation($request, $project, $existing ? 'edit' : 'create');
        $data['path_coordinates'] = $this->coordinatePathPayload($data['path_coordinates'] ?? null, 'path_coordinates');

        ReferenceRoute::updateOrCreate(['route_code' => $data['route_code']], $data);

        return back()->with('message', 'Reference route berhasil disimpan.');
    }

    public function storeCorridor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'reference_route_id' => ['nullable', 'exists:reference_routes,id'],
            'corridor_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'path_coordinates' => ['nullable'],
            'status' => ['required', 'string', 'max:50'],
            'status_metadata' => ['nullable'],
            'notes' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->assertWorkspaceInProject($data['workspace_id'], $project->id);
        $this->assertNullableBelongsToProject(ReferenceRoute::class, $data['reference_route_id'] ?? null, $project->id);
        $existing = CorridorMonitoring::where('corridor_code', $data['corridor_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeSpatialMutation($request, $project, $existing ? 'edit' : 'create');

        $data['path_coordinates'] = $this->coordinatePathPayload($data['path_coordinates'] ?? null, 'path_coordinates');
        $data['status_metadata'] = $this->jsonPayload($data['status_metadata'] ?? null, 'status_metadata');

        CorridorMonitoring::updateOrCreate(['corridor_code' => $data['corridor_code']], $data);

        return back()->with('message', 'Corridor monitoring berhasil disimpan.');
    }

    public function storeReferencePoint(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
            'corridor_id' => ['nullable', 'exists:corridor_monitorings,id'],
            'reference_route_id' => ['nullable', 'exists:reference_routes,id'],
            'point_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'point_type' => ['required', 'string', 'max:100'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->assertNullableWorkspaceInProject($data['workspace_id'] ?? null, $project->id);
        $this->assertNullableBelongsToProject(CorridorMonitoring::class, $data['corridor_id'] ?? null, $project->id);
        $this->assertNullableBelongsToProject(ReferenceRoute::class, $data['reference_route_id'] ?? null, $project->id);
        $existing = ReferencePoint::where('point_code', $data['point_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeSpatialMutation($request, $project, $existing ? 'edit' : 'create');

        $data = $this->applyParsedCoordinate($data);

        ReferencePoint::updateOrCreate(['point_code' => $data['point_code']], $data);

        return back()->with('message', 'Reference point berhasil disimpan.');
    }

    public function storeStationSpatialReference(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'corridor_id' => ['nullable', 'exists:corridor_monitorings,id'],
            'reference_route_id' => ['nullable', 'exists:reference_routes,id'],
            'reference_point_id' => ['nullable', 'exists:reference_points,id'],
            'monitoring_station_id' => ['nullable', 'exists:monitoring_stations,id'],
            'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
            'placement_role' => ['required', 'string', 'max:100'],
            'station_offset' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->assertWorkspaceInProject($data['workspace_id'], $project->id);
        $this->assertNullableBelongsToProject(CorridorMonitoring::class, $data['corridor_id'] ?? null, $project->id);
        $this->assertNullableBelongsToProject(ReferenceRoute::class, $data['reference_route_id'] ?? null, $project->id);
        $this->assertNullableBelongsToProject(ReferencePoint::class, $data['reference_point_id'] ?? null, $project->id);
        $this->assertNullableBelongsToWorkspace(MonitoringStation::class, $data['monitoring_station_id'] ?? null, $data['workspace_id']);
        $this->assertNullableBelongsToWorkspace(WarningStation::class, $data['warning_station_id'] ?? null, $data['workspace_id']);

        if (empty($data['monitoring_station_id']) && empty($data['warning_station_id'])) {
            throw ValidationException::withMessages([
                'station' => 'Pilih monitoring station atau warning station untuk spatial reference.',
            ]);
        }

        $existing = StationSpatialReference::query()
            ->where('project_id', $project->id)
            ->where('workspace_id', $data['workspace_id'])
            ->where('monitoring_station_id', $data['monitoring_station_id'] ?? null)
            ->where('warning_station_id', $data['warning_station_id'] ?? null)
            ->where('corridor_id', $data['corridor_id'] ?? null)
            ->first();

        $this->authorizeSpatialMutation($request, $project, $existing ? 'edit' : 'create');

        StationSpatialReference::updateOrCreate(
            [
                'project_id' => $data['project_id'],
                'workspace_id' => $data['workspace_id'],
                'monitoring_station_id' => $data['monitoring_station_id'] ?? null,
                'warning_station_id' => $data['warning_station_id'] ?? null,
                'corridor_id' => $data['corridor_id'] ?? null,
            ],
            $data
        );

        return back()->with('message', 'Station spatial reference berhasil disimpan.');
    }

    public function storeMonitoringStation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'project_id' => ['nullable', 'exists:resq_projects,id'],
            'corridor_id' => ['nullable', 'exists:corridor_monitorings,id'],
            'station_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'station_type' => ['nullable', 'string', 'max:100'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'logger_id' => ['nullable', 'string', 'max:255'],
            'logger_status' => ['required', 'string', 'max:50'],
            'connectivity_status' => ['required', 'string', 'max:50'],
            'registration_status' => ['nullable', 'string', 'max:50'],
            'registered_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data = $this->applyParsedCoordinate($data);

        $workspace = GeospatialWorkspace::findOrFail($data['workspace_id']);
        $data['project_id'] = $data['project_id'] ?? $workspace->project_id;
        abort_if((int) $data['project_id'] !== (int) $workspace->project_id, 403);
        $this->assertNullableBelongsToProject(CorridorMonitoring::class, $data['corridor_id'] ?? null, $workspace->project_id);
        $data['station_type'] = $data['station_type'] ?? 'environmental_monitoring';
        $data['registration_status'] = $data['registration_status'] ?? 'registered';
        $data['registered_at'] = $data['registered_at'] ?? now();
        $data['registered_by_user_id'] = $request->user()?->id;
        $existing = MonitoringStation::where('station_code', $data['station_code'])->first();
        abort_if($existing && (int) $existing->workspace?->project_id !== (int) $workspace->project_id, 403);
        $this->authorizeAssetRegistryMutation($request);
        $this->authorizeSpatialMutation($request, $workspace->project, $existing ? 'edit' : 'create');

        MonitoringStation::updateOrCreate(['station_code' => $data['station_code']], $data);

        return back()->with('message', 'Monitoring station berhasil disimpan.');
    }

    public function storeWarningStation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'project_id' => ['nullable', 'exists:resq_projects,id'],
            'monitoring_station_id' => ['nullable', 'exists:monitoring_stations,id'],
            'station_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'zone_id' => ['nullable', 'string', 'max:255'],
            'administrative_location' => ['nullable', 'string', 'max:255'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'controller_id' => ['nullable', 'string', 'max:255'],
            'controller_model' => ['nullable', 'string', 'max:255'],
            'controller_vendor' => ['nullable', 'string', 'max:255'],
            'controller_status' => ['required', 'string', 'max:50'],
            'registration_status' => ['nullable', 'string', 'max:50'],
            'registered_at' => ['nullable', 'date'],
            'output_devices' => ['nullable', 'array'],
            'status' => ['required', 'string', 'max:50'],
            'public_warning_enabled' => ['nullable', 'boolean'],
            'ack_response' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['public_warning_enabled'] = $request->boolean('public_warning_enabled');
        $data = $this->applyParsedCoordinate($data);

        $workspace = GeospatialWorkspace::findOrFail($data['workspace_id']);
        $data['project_id'] = $data['project_id'] ?? $workspace->project_id;
        abort_if((int) $data['project_id'] !== (int) $workspace->project_id, 403);
        $existing = WarningStation::where('station_code', $data['station_code'])->first();
        abort_if($existing && (int) $existing->workspace?->project_id !== (int) $workspace->project_id, 403);
        $this->assertNullableBelongsToWorkspace(MonitoringStation::class, $data['monitoring_station_id'] ?? null, $workspace->id);
        $data['registration_status'] = $data['registration_status'] ?? 'registered';
        $data['registered_at'] = $data['registered_at'] ?? now();
        $data['registered_by_user_id'] = $request->user()?->id;
        $this->authorizeAssetRegistryMutation($request);
        $this->authorizeSpatialMutation($request, $workspace->project, $existing ? 'edit' : 'create');

        WarningStation::updateOrCreate(['station_code' => $data['station_code']], $data);

        return back()->with('message', 'Warning station berhasil disimpan.');
    }

    public function storeSensor(Request $request): RedirectResponse
    {
        $request->merge(['input_source' => $request->input('input_source', 'data_logger')]);
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'monitoring_station_id' => ['required', 'exists:monitoring_stations,id'],
            'input_source' => ['required', Rule::in(['data_logger', 'mqtt'])],
            'data_logger_id' => ['nullable', 'exists:data_loggers,id'],
            'mqtt_configuration_id' => ['nullable', 'required_if:input_source,mqtt', 'exists:mqtt_configurations,id'],
            'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
            'mst_prefix_id' => ['required', 'exists:mst_prefixes,id'],
            'slave_id' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'function_code' => ['required', Rule::in(['FC01', 'FC02', 'FC03', 'FC04'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:125'],
            'poll_interval_ms' => ['required', 'integer', 'min:250', 'max:60000'],
            'sensor_code' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'parameter' => ['nullable', 'string', 'max:255'],
            'weather_parameters' => ['nullable', 'array'],
            'weather_parameters.*' => ['string', 'max:255'],
            'canonical_parameter_id' => ['nullable', 'exists:canonical_parameters,id'],
            'mapping_profile_code' => ['nullable', 'string', 'max:255'],
            'source_parameter' => ['nullable', 'string', 'max:255'],
            'source_unit' => ['nullable', 'string', 'max:50'],
            'value_origin' => ['nullable', Rule::in(['direct_measurement', 'device_processed'])],
            'byte_order' => ['nullable', 'string', 'max:50'],
            'value' => ['nullable', 'string', 'max:255'],
            'threshold' => ['nullable', 'string', 'max:255'],
            'data_type' => ['required', Rule::in([
                'float32',
                'float64',
                'int8',
                'int16',
                'int32',
                'int64',
                'uint8',
                'uint16',
                'uint32',
                'uint64',
                'boolean',
                'string',
                'ascii',
                'hex',
                'byte',
                'raw',
            ])],
            'scale_factor' => ['required', 'numeric'],
            'offset' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reading_method' => ['required', 'string', 'max:50'],
            'alert_level' => ['required', 'string', 'max:50'],
            'rule' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data['weather_parameters'] = $data['type'] === 'weather_station'
            ? $this->weatherParametersForSensor((int) $data['quantity'], $data['weather_parameters'] ?? [], $data['parameter'] ?? null)
            : null;

        $workspace = GeospatialWorkspace::findOrFail($data['workspace_id']);
        $this->assertBelongsToWorkspace(MonitoringStation::class, $data['monitoring_station_id'], $workspace->id);
        $this->assertNullableBelongsToWorkspace(WarningStation::class, $data['warning_station_id'] ?? null, $workspace->id);
        if ($data['input_source'] === 'mqtt') {
            $mqttConfiguration = MqttConfiguration::findOrFail($data['mqtt_configuration_id']);
            abort_unless((int) $mqttConfiguration->project_id === (int) $workspace->project_id && $mqttConfiguration->consumer_enabled, 422);
            if (! empty($data['source_parameter']) && data_get($mqttConfiguration->example_payload ?? [], $data['source_parameter']) === null) {
                throw ValidationException::withMessages(['source_parameter' => 'JSON path tidak ditemukan pada example output MQTT.']);
            }
            $data['data_logger_id'] = null;
        } else {
            $this->assertNullableDataLoggerForStation($data['data_logger_id'] ?? null, $data['monitoring_station_id']);
            $data['mqtt_configuration_id'] = null;
        }
        $existing = Sensor::where('sensor_code', $data['sensor_code'])->first();
        abort_if($existing && (int) $existing->workspace?->project_id !== (int) $workspace->project_id, 403);
        $this->authorizeAssetRegistryMutation($request);
        $this->authorizeSpatialMutation($request, $workspace->project, $existing ? 'edit' : 'create');

        $mappingData = [
            'canonical_parameter_id' => $data['canonical_parameter_id'] ?? null,
            'mapping_profile_code' => $data['mapping_profile_code'] ?? null,
            'source_parameter' => $data['source_parameter'] ?? null,
            'source_unit' => $data['source_unit'] ?? null,
            'value_origin' => $data['value_origin'] ?? 'direct_measurement',
            'byte_order' => $data['byte_order'] ?? null,
        ];
        unset(
            $data['canonical_parameter_id'],
            $data['mapping_profile_code'],
            $data['source_parameter'],
            $data['source_unit'],
            $data['value_origin'],
            $data['byte_order']
        );

        $addressAlreadyUsed = $data['input_source'] === 'data_logger' && Sensor::query()
            ->where('mst_prefix_id', $data['mst_prefix_id'])
            ->where('slave_id', $data['slave_id'])
            ->where('address', $data['address'])
            ->where('sensor_code', '!=', $data['sensor_code'])
            ->exists();

        if ($addressAlreadyUsed) {
            return back()
                ->withErrors(['address' => 'Kombinasi Prefix Sensors, Slave ID, dan Address sudah dipakai sensor lain.'])
                ->withInput();
        }

        $sensor = Sensor::updateOrCreate(['sensor_code' => $data['sensor_code']], $data);
        $this->upsertSensorMappingFromRegistration($sensor, $mappingData);

        return back()->with('message', 'Sensor berhasil disimpan.');
    }

    public function storeResponsePlan(Request $request): RedirectResponse
    {
        $this->authorizeAssetRegistryMutation($request);

        ResponsePlan::create([
            ...$request->validate([
                'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
                'sensor_id' => ['nullable', 'exists:sensors,id'],
                'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
                'notes' => ['nullable', 'string'],
            ]),
            'dashboard_notif' => $request->boolean('dashboard_notif'),
            'sms_blasting' => $request->boolean('sms_blasting'),
            'warning_station_act' => $request->boolean('warning_station_act'),
        ]);

        return back()->with('message', 'Response plan berhasil ditambahkan.');
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        abort_unless($this->authorizationService->canAccessProject($request->user(), $project), 403);
    }

    private function authorizeProjectConfigurationMutation(Request $request, ?Project $project, string $action): void
    {
        $user = $request->user();

        $allowed = match ($action) {
            'create' => $this->authorizationService->canCreateProject($user),
            'edit' => $project && $this->authorizationService->canEditProject($user, $project),
            'delete' => $project && $this->authorizationService->canDeleteProject($user, $project),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function authorizeSpatialMutation(Request $request, Project $project, string $action): void
    {
        $user = $request->user();

        $allowed = match ($action) {
            'create' => $this->authorizationService->canCreateSpatialResource($user, $project),
            'edit' => $this->authorizationService->canEditSpatialResource($user, $project),
            'delete' => $this->authorizationService->canDeleteSpatialResource($user, $project),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function authorizeAssetRegistryMutation(Request $request): void
    {
        abort_unless($this->authorizationService->canMutateAssetRegistry($request->user()), 403);
    }

    private function assertWorkspaceInProject(int|string $workspaceId, int $projectId): GeospatialWorkspace
    {
        $workspace = GeospatialWorkspace::findOrFail($workspaceId);
        abort_if((int) $workspace->project_id !== (int) $projectId, 403);

        return $workspace;
    }

    private function assertNullableWorkspaceInProject(int|string|null $workspaceId, int $projectId): ?GeospatialWorkspace
    {
        if (empty($workspaceId)) {
            return null;
        }

        return $this->assertWorkspaceInProject($workspaceId, $projectId);
    }

    private function assertBelongsToWorkspace(string $modelClass, int|string $id, int $workspaceId): void
    {
        $model = $modelClass::findOrFail($id);
        abort_if((int) $model->workspace_id !== (int) $workspaceId, 403);
    }

    private function assertNullableBelongsToWorkspace(string $modelClass, int|string|null $id, int $workspaceId): void
    {
        if (empty($id)) {
            return;
        }

        $this->assertBelongsToWorkspace($modelClass, $id, $workspaceId);
    }

    private function assertNullableDataLoggerForStation(int|string|null $id, int|string $monitoringStationId): void
    {
        if (empty($id)) {
            return;
        }

        $dataLogger = DataLogger::findOrFail($id);
        abort_if(
            $dataLogger->monitoring_station_id !== null
            && (int) $dataLogger->monitoring_station_id !== (int) $monitoringStationId,
            403
        );
    }

    private function assertNullableBelongsToProject(string $modelClass, int|string|null $id, int $projectId): void
    {
        if (empty($id)) {
            return;
        }

        $model = $modelClass::findOrFail($id);
        abort_if((int) $model->project_id !== (int) $projectId, 403);
    }

    private function upsertSensorMappingFromRegistration(Sensor $sensor, array $mappingData): void
    {
        if (empty($mappingData['canonical_parameter_id'])) {
            return;
        }

        $parameter = CanonicalParameter::findOrFail($mappingData['canonical_parameter_id']);
        $profileCode = $mappingData['mapping_profile_code']
            ?: 'MAP-' . $sensor->sensor_code . '-' . $parameter->field_identity;

        SensorMappingProfile::updateOrCreate(
            ['profile_code' => $profileCode],
            [
                'sensor_id' => $sensor->id,
                'communication_path' => $sensor->dataLogger?->logger_code ?: $sensor->monitoringStation?->station_code,
                'slave_id' => is_numeric($sensor->slave_id) ? (int) $sensor->slave_id : null,
                'source_parameter' => $mappingData['source_parameter'] ?: ($sensor->parameter ?: $parameter->field_identity),
                'source_unit' => $mappingData['source_unit'] ?: $sensor->unit,
                'register_address' => $sensor->address,
                'function_code' => $sensor->function_code,
                'value_type' => $sensor->data_type,
                'data_length' => max((int) ($sensor->quantity ?? 1), 1),
                'byte_order' => $mappingData['byte_order'] ?: null,
                'scale_factor' => $sensor->scale_factor ?? 1,
                'offset' => $sensor->offset ?? 0,
                'canonical_parameter_id' => $parameter->id,
                'value_origin' => $mappingData['value_origin'] ?: 'direct_measurement',
                'status' => 'active',
            ]
        );
    }

    private function jsonPayload(mixed $payload, string $field): ?array
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        if (is_array($payload)) {
            return $payload;
        }

        $decoded = json_decode((string) $payload, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'Format JSON tidak valid.',
            ]);
        }

        return $decoded;
    }

    private function coordinatePathPayload(mixed $payload, string $field): ?array
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $coordinates = $this->jsonPayload($payload, $field);

        if ($coordinates === null) {
            return null;
        }

        $normalized = collect($coordinates)->map(function ($point) use ($field) {
            if (! is_array($point) || count($point) < 2 || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                throw ValidationException::withMessages([
                    $field => 'Path harus memakai format [[lat, lng], [lat, lng]].',
                ]);
            }

            return [(float) $point[0], (float) $point[1]];
        })->values()->all();

        return count($normalized) >= 2 ? $normalized : null;
    }

    private function projectForResource(object $model): ?Project
    {
        if ($model instanceof Project) {
            return $model;
        }

        if (property_exists($model, 'project_id') || isset($model->project_id)) {
            return Project::find($model->project_id);
        }

        if (property_exists($model, 'workspace_id') || isset($model->workspace_id)) {
            $workspace = GeospatialWorkspace::find($model->workspace_id);

            return $workspace?->project;
        }

        if ($model instanceof ResponsePlan) {
            if ($model->workspace_id) {
                return GeospatialWorkspace::find($model->workspace_id)?->project;
            }

            if ($model->sensor_id) {
                return Sensor::find($model->sensor_id)?->workspace?->project;
            }

            if ($model->warning_station_id) {
                return WarningStation::find($model->warning_station_id)?->workspace?->project;
            }
        }

        return null;
    }

    private function weatherParametersForSensor(int $quantity, array $selected = [], ?string $hint = null): array
    {
        return collect($selected)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $models = [
            'project' => Project::class,
            'workspace' => GeospatialWorkspace::class,
            'monitoring' => MonitoringStation::class,
            'warning' => WarningStation::class,
            'sensor' => Sensor::class,
            'response-plan' => ResponsePlan::class,
            'information-layer' => SpatialInformationLayer::class,
            'reference-route' => ReferenceRoute::class,
            'corridor' => CorridorMonitoring::class,
            'reference-point' => ReferencePoint::class,
            'station-spatial-reference' => StationSpatialReference::class,
        ];

        abort_unless(isset($models[$type]), 404);

        $model = $models[$type]::findOrFail($id);
        $project = $this->projectForResource($model);
        abort_unless($project, 404);

        if (in_array($type, ['monitoring', 'warning', 'sensor', 'response-plan'], true)) {
            $this->authorizeAssetRegistryMutation(request());
        }

        $this->authorizeSpatialMutation(request(), $project, 'delete');

        if ($type === 'sensor') {
            $this->cleanupSensorReferences($id);
        }

        $model->delete();

        return back()->with('message', 'Data berhasil dihapus.');
    }

    private function cleanupSensorReferences(int $sensorId): void
    {
        // Hapus ID sensor dari monitored_sensor_ids di semua connectivity configs
        $connectivities = ConnectivityConfig::whereJsonContains('serial_settings->monitored_sensor_ids', $sensorId)->get();

        foreach ($connectivities as $connectivity) {
            $settings = $connectivity->serial_settings ?? [];
            $sensorIds = collect($settings['monitored_sensor_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id !== $sensorId)
                ->values()
                ->all();
            $settings['monitored_sensor_ids'] = $sensorIds;
            $connectivity->update(['serial_settings' => $settings]);
        }

        // Hapus telemetry readings yang terkait sensor ini
        if (Schema::hasTable('telemetry_readings')) {
            TelemetryReading::where('sensor_id', $sensorId)->delete();
        }

        // Hapus response plans yang terkait sensor ini
        if (Schema::hasTable('response_plans')) {
            ResponsePlan::where('sensor_id', $sensorId)->delete();
        }

        // Hapus sensor mapping profiles yang terkait sensor ini
        if (Schema::hasTable('sensor_mapping_profiles')) {
            SensorMappingProfile::where('sensor_id', $sensorId)->delete();
        }
    }

    private function viewData(): array
    {
        $provinces = $this->provinces();

        if (! Schema::hasTable('resq_projects')) {
            return [
                'project' => config('resq_dummy.project'),
                'projects' => collect([]),
                'clusters' => collect(config('resq_dummy.clusters')),
                'monitoringStations' => collect(config('resq_dummy.monitoring_stations')),
                'warningStations' => collect(config('resq_dummy.warning_stations')),
                'sensors' => collect(config('resq_dummy.sensors')),
                'dataLoggers' => collect(config('resq_dummy.data_loggers')),
                'connectivity' => collect(config('resq_dummy.connectivity')),
                'credentials' => collect(config('resq_dummy.credentials')),
                'mqttProjects' => collect([]),
                'mqttConfigurations' => collect([]),
                'telemetryReadings' => collect([]),
                'mstPrefixes' => collect([]),
                'responsePlans' => collect([]),
                'informationLayers' => collect([]),
                'referenceRoutes' => collect([]),
                'corridors' => collect([]),
                'referencePoints' => collect([]),
                'stationSpatialReferences' => collect([]),
                'warningStationTelemetryConfigs' => collect([]),
                'warningStationDevices' => collect([]),
                'spatialResources' => [],
                'permissions' => [
                    'canCreateSpatial' => false,
                    'canEditSpatial' => false,
                    'canDeleteSpatial' => false,
                    'canMutateAssetRegistry' => false,
                ],
                'provinces' => $provinces,
                'databaseReady' => false,
            ];
        }

        $user = auth()->user();
        $projectQuery = Project::query();
        $workspaceQuery = GeospatialWorkspace::query();
        if ($user) {
            $this->authorizationService->scopeProjectsForUser($user, $projectQuery);
            $this->authorizationService->scopeWorkspacesForUser($user, $workspaceQuery);
        }

        $projectModels = $projectQuery->latest()->get();
        $accessibleProjectIds = $projectModels->pluck('id')->all();
        $workspaceModels = $workspaceQuery->with('project')->latest()->get();
        $accessibleWorkspaceIds = $workspaceModels->pluck('id')->all();
        $monitoringModels = MonitoringStation::with(['workspace.project', 'project', 'corridor'])
            ->whereIn('workspace_id', $accessibleWorkspaceIds)
            ->latest()
            ->get();
        $warningModels = WarningStation::with(['project', 'workspace.project', 'monitoringStation'])
            ->whereIn('workspace_id', $accessibleWorkspaceIds)
            ->latest()
            ->get();
        $sensorQuery = Sensor::with(['workspace', 'monitoringStation', 'dataLogger', 'mqttConfiguration', 'warningStation', 'mstPrefix']);
        $sensorQuery->whereIn('workspace_id', $accessibleWorkspaceIds);
        if (Schema::hasTable('sensor_mapping_profiles')) {
            $sensorQuery->with('mappingProfile');
        }
        $sensorModels = $sensorQuery->latest()->get();
        $informationLayerModels = Schema::hasTable('spatial_information_layers')
            ? SpatialInformationLayer::with(['project', 'workspace'])->whereIn('project_id', $accessibleProjectIds)->latest()->get()
            : collect();
        $referenceRouteModels = Schema::hasTable('reference_routes')
            ? ReferenceRoute::with(['project', 'workspace'])
                ->select(['id', 'project_id', 'workspace_id', 'route_code', 'name', 'route_type', 'status', 'notes', 'total_length', 'corridor_code', 'created_at', 'updated_at'])
                ->whereIn('project_id', $accessibleProjectIds)->latest()->get()
            : collect();
        $corridorModels = Schema::hasTable('corridor_monitorings')
            ? CorridorMonitoring::with(['project', 'workspace', 'referenceRoute' => fn ($q) => $q->select(['id', 'route_code', 'name'])])
                ->select(['id', 'project_id', 'workspace_id', 'reference_route_id', 'corridor_code', 'name', 'status', 'status_metadata', 'notes', 'created_at', 'updated_at'])
                ->whereIn('project_id', $accessibleProjectIds)->latest()->get()
            : collect();
        $referencePointModels = Schema::hasTable('reference_points')
            ? ReferencePoint::with(['project', 'workspace', 'corridor', 'referenceRoute'])->whereIn('project_id', $accessibleProjectIds)->latest()->get()
            : collect();
        $stationSpatialReferenceModels = Schema::hasTable('station_spatial_references')
            ? StationSpatialReference::with(['project', 'workspace', 'corridor', 'referenceRoute', 'referencePoint', 'monitoringStation', 'warningStation'])
                ->whereIn('project_id', $accessibleProjectIds)
                ->latest()
                ->get()
            : collect();
        $warningTelemetryConfigModels = Schema::hasTable('warning_station_telemetry_configs')
            ? WarningStationTelemetryConfig::with(['project', 'warningStation'])->whereIn('project_id', $accessibleProjectIds)->latest()->get()
            : collect();
        $warningDeviceModels = Schema::hasTable('warning_station_devices')
            ? WarningStationDevice::with(['project', 'warningStation'])->whereIn('project_id', $accessibleProjectIds)->latest()->get()
            : collect();
        $mstPrefixes = Schema::hasTable('mst_prefixes') ? MstPrefix::latest()->get() : collect();
        $dataLoggerModels = Schema::hasTable('data_loggers')
            ? DataLogger::with('monitoringStation')->latest()->get()
            : collect();
        $connectivityModels = Schema::hasTable('connectivity_configs')
            ? ConnectivityConfig::with('dataLogger')->latest()->get()
            : collect();
        $credentialModels = Schema::hasTable('device_credentials')
            ? DeviceCredential::with('dataLogger')->latest()->get()
            : collect();
        $telemetryModels = Schema::hasTable('telemetry_readings')
            ? TelemetryReading::with(['sensor.monitoringStation', 'dataLogger'])->latest('received_at')->latest()->limit(100)->get()
            : collect();
        $mqttConfigurations = Schema::hasTable('mqtt_configurations')
            ? MqttConfiguration::with(['project', 'sensors'])->whereIn('project_id', $accessibleProjectIds)->orderBy('name')->get()
            : collect();

        $projects = $projectModels->map(fn (Project $project) => [
            'db_id' => $project->id,
            'id' => $project->project_code,
            'name' => $project->name,
            'owner' => $project->owner,
            'date' => $project->project_date,
            'status' => $project->status,
        ]);

        $workspaces = $workspaceModels->map(fn (GeospatialWorkspace $workspace) => [
            'db_id' => $workspace->id,
            'project_db_id' => $workspace->project_id,
            'id' => $workspace->workspace_code,
            'project_id' => $workspace->project?->project_code,
            'name' => $workspace->name,
            'hazard' => $workspace->hazard,
            'province' => $workspace->province,
            'city' => $workspace->city,
            'beneficiaries' => $workspace->beneficiaries,
            'latitude' => $workspace->latitude,
            'longitude' => $workspace->longitude,
            'status' => $workspace->status,
            'basemap_provider' => $workspace->basemap_provider ?? 'OpenStreetMap',
            'basemap_tile_url' => $workspace->basemap_tile_url,
            'default_zoom' => $workspace->default_zoom ?? 5,
            'map_bounds' => $workspace->map_bounds ?? [],
            'monitoring_station_id' => optional($monitoringModels->firstWhere('workspace_id', $workspace->id))->station_code,
            'warning_station_id' => optional($warningModels->firstWhere('workspace_id', $workspace->id))->station_code,
        ]);

        $monitoringStations = $monitoringModels->map(fn (MonitoringStation $station) => [
            'db_id' => $station->id,
            'project_db_id' => $station->project_id ?? $station->workspace?->project_id,
            'corridor_db_id' => $station->corridor_id,
            'workspace_db_id' => $station->workspace_id,
            'id' => $station->station_code,
            'project_id' => $station->project?->project_code ?? $station->workspace?->project?->project_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'corridor_id' => $station->corridor?->corridor_code,
            'name' => $station->name,
            'station_type' => $station->station_type ?? 'environmental_monitoring',
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'logger_id' => $station->logger_id,
            'logger_status' => $station->logger_status,
            'connectivity_status' => $station->connectivity_status,
            'registration_status' => $station->registration_status ?? 'registered',
            'registered_at' => optional($station->registered_at)->format('Y-m-d\TH:i'),
            'registered_by_user_id' => $station->registered_by_user_id,
            'warning_station_id' => optional($warningModels->firstWhere('monitoring_station_id', $station->id))->station_code,
            'status' => $station->status,
        ]);

        $warningStations = $warningModels->map(fn (WarningStation $station) => [
            'db_id' => $station->id,
            'project_db_id' => $station->project_id ?? $station->workspace?->project_id,
            'workspace_db_id' => $station->workspace_id,
            'monitoring_station_db_id' => $station->monitoring_station_id,
            'id' => $station->station_code,
            'project_id' => $station->project?->project_code ?? $station->workspace?->project?->project_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'source_monitoring_station_id' => $station->monitoringStation?->station_code,
            'name' => $station->name,
            'zone_id' => $station->zone_id,
            'administrative_location' => $station->administrative_location,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'controller_id' => $station->controller_id,
            'controller_model' => $station->controller_model,
            'controller_vendor' => $station->controller_vendor,
            'controller_status' => $station->controller_status,
            'registration_status' => $station->registration_status ?? 'registered',
            'registered_at' => optional($station->registered_at)->format('Y-m-d\TH:i'),
            'registered_by_user_id' => $station->registered_by_user_id,
            'output_devices' => $station->output_devices ?? [],
            'status' => $station->status,
            'public_warning_enabled' => $station->public_warning_enabled,
            'ack_response' => $station->ack_response,
            'notes' => $station->notes,
        ]);

        $sensors = $sensorModels->map(fn (Sensor $sensor) => [
            'db_id' => $sensor->id,
            'workspace_db_id' => $sensor->workspace_id,
            'monitoring_station_db_id' => $sensor->monitoring_station_id,
            'data_logger_db_id' => $sensor->data_logger_id,
            'input_source' => $sensor->input_source ?? 'data_logger',
            'mqtt_configuration_db_id' => $sensor->mqtt_configuration_id,
            'mqtt_configuration_code' => $sensor->mqttConfiguration?->configuration_code,
            'warning_station_db_id' => $sensor->warning_station_id,
            'mst_prefix_db_id' => $sensor->mst_prefix_id,
            'id' => $sensor->sensor_code,
            'cluster_id' => $sensor->workspace?->workspace_code,
            'monitoring_station_id' => $sensor->monitoringStation?->station_code,
            'data_logger_id' => $sensor->dataLogger?->logger_code,
            'warning_station_id' => $sensor->warningStation?->station_code,
            'mst_prefix' => $sensor->mstPrefix?->prefix_code,
            'slave_id' => $sensor->slave_id,
            'address' => $sensor->address,
            'function_code' => $sensor->function_code ?? 'FC03',
            'quantity' => $sensor->quantity ?? 1,
            'poll_interval_ms' => $sensor->poll_interval_ms ?? 1000,
            'type' => $sensor->type,
            'parameter' => $sensor->parameter,
            'weather_parameters' => $sensor->weather_parameters ?? [],
            'canonical_parameter_db_id' => $sensor->mappingProfile?->canonical_parameter_id,
            'source_parameter' => $sensor->mappingProfile?->source_parameter,
            'source_unit' => $sensor->mappingProfile?->source_unit,
            'byte_order' => $sensor->mappingProfile?->byte_order,
            'value_origin' => $sensor->mappingProfile?->value_origin,
            'value' => $sensor->value,
            'threshold' => $sensor->threshold,
            'data_type' => $sensor->data_type,
            'scale_factor' => $sensor->scale_factor,
            'offset' => $sensor->offset,
            'unit' => $sensor->unit,
            'reading_method' => $sensor->reading_method,
            'alert_level' => $sensor->alert_level,
            'rule' => $sensor->rule,
            'status' => $sensor->status,
            'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
            'is_canonical_mapped' => $sensor->relationLoaded('mappingProfile') && $sensor->mappingProfile !== null,
        ]);

        $informationLayers = $informationLayerModels->map(fn (SpatialInformationLayer $layer) => [
            'db_id' => $layer->id,
            'project_db_id' => $layer->project_id,
            'workspace_db_id' => $layer->workspace_id,
            'id' => $layer->layer_code,
            'project_id' => $layer->project?->project_code,
            'workspace_id' => $layer->workspace?->workspace_code,
            'name' => $layer->name,
            'layer_type' => $layer->layer_type,
            'source_url' => $layer->source_url,
            'layer_payload' => $layer->layer_payload ?? [],
            'style_color' => $layer->style_color,
            'visible_by_default' => $layer->visible_by_default,
            'sort_order' => $layer->sort_order,
            'status' => $layer->status,
        ]);

        $referenceRoutes = $referenceRouteModels->map(fn (ReferenceRoute $route) => $this->routeRow($route));
        $corridors = $corridorModels->map(fn (CorridorMonitoring $corridor) => $this->corridorRow($corridor));
        $referencePoints = $referencePointModels->map(fn (ReferencePoint $point) => [
            'db_id' => $point->id,
            'project_db_id' => $point->project_id,
            'workspace_db_id' => $point->workspace_id,
            'corridor_db_id' => $point->corridor_id,
            'reference_route_db_id' => $point->reference_route_id,
            'id' => $point->point_code,
            'project_id' => $point->project?->project_code,
            'workspace_id' => $point->workspace?->workspace_code,
            'corridor_id' => $point->corridor?->corridor_code,
            'reference_route_id' => $point->referenceRoute?->route_code,
            'name' => $point->name,
            'point_type' => $point->point_type,
            'coordinate' => $point->coordinate,
            'latitude' => $point->latitude,
            'longitude' => $point->longitude,
            'status' => $point->status,
            'notes' => $point->notes,
        ]);

        $stationSpatialReferences = $stationSpatialReferenceModels->map(fn (StationSpatialReference $reference) => [
            'db_id' => $reference->id,
            'project_db_id' => $reference->project_id,
            'workspace_db_id' => $reference->workspace_id,
            'corridor_db_id' => $reference->corridor_id,
            'reference_route_db_id' => $reference->reference_route_id,
            'reference_point_db_id' => $reference->reference_point_id,
            'monitoring_station_db_id' => $reference->monitoring_station_id,
            'warning_station_db_id' => $reference->warning_station_id,
            'project_id' => $reference->project?->project_code,
            'workspace_id' => $reference->workspace?->workspace_code,
            'corridor_id' => $reference->corridor?->corridor_code,
            'reference_route_id' => $reference->referenceRoute?->route_code,
            'reference_point_id' => $reference->referencePoint?->point_code,
            'monitoring_station_id' => $reference->monitoringStation?->station_code,
            'warning_station_id' => $reference->warningStation?->station_code,
            'placement_role' => $reference->placement_role,
            'station_offset' => $reference->station_offset,
            'status' => $reference->status,
        ]);
        $warningStationTelemetryConfigs = $warningTelemetryConfigModels->map(fn (WarningStationTelemetryConfig $config) => [
            'db_id' => $config->id,
            'project_db_id' => $config->project_id,
            'warning_station_db_id' => $config->warning_station_id,
            'project_id' => $config->project?->project_code,
            'warning_station_id' => $config->warningStation?->station_code,
            'id' => $config->config_code,
            'broker_config_ref' => $config->broker_config_ref,
            'protocol' => $config->protocol,
            'host_or_endpoint' => $config->host_or_endpoint,
            'port' => $config->port,
            'topic' => $config->topic,
            'qos' => $config->qos,
            'retain' => $config->retain,
            'credential_ref' => $config->credential_ref,
            'connection_status' => $config->connection_status,
            'last_connected_at' => optional($config->last_connected_at)->toISOString(),
            'last_seen_at' => optional($config->last_seen_at)->toISOString(),
        ]);
        $warningStationDevices = $warningDeviceModels->map(fn (WarningStationDevice $device) => [
            'db_id' => $device->id,
            'project_db_id' => $device->project_id,
            'warning_station_db_id' => $device->warning_station_id,
            'project_id' => $device->project?->project_code,
            'warning_station_id' => $device->warningStation?->station_code,
            'id' => $device->device_code,
            'device_type' => $device->device_type,
            'name' => $device->name,
            'vendor' => $device->vendor,
            'model' => $device->model,
            'serial_number' => $device->serial_number,
            'expected' => $device->expected,
            'availability_state' => $device->availability_state,
            'health_state' => $device->health_state,
            'last_heartbeat_at' => optional($device->last_heartbeat_at)->toISOString(),
            'status' => $device->status,
            'notes' => $device->notes,
        ]);

        $firstProject = $projectModels->first();
        $permissions = [
            'canCreateSpatial' => $user && $firstProject ? $this->authorizationService->canCreateSpatialResource($user, $firstProject) : false,
            'canEditSpatial' => $user && $firstProject ? $this->authorizationService->canEditSpatialResource($user, $firstProject) : false,
            'canDeleteSpatial' => $user && $firstProject ? $this->authorizationService->canDeleteSpatialResource($user, $firstProject) : false,
            'canMutateAssetRegistry' => $user ? $this->authorizationService->canMutateAssetRegistry($user) : false,
        ];

        return [
            'project' => $projects->first() ?? config('resq_dummy.project'),
            'projects' => $projects,
            'clusters' => $workspaces,
            'monitoringStations' => $monitoringStations,
            'warningStations' => $warningStations,
            'sensors' => $sensors,
            'dataLoggers' => $this->dataLoggersFromModels($dataLoggerModels),
            'connectivity' => $this->connectivityFromModels($connectivityModels),
            'credentials' => $this->credentialsFromModels($credentialModels),
            'mqttProjects' => $projectModels,
            'mqttConfigurations' => $mqttConfigurations,
            'telemetryReadings' => $this->telemetryFromModels($telemetryModels),
            'mstPrefixes' => $mstPrefixes,
            'responsePlans' => ResponsePlan::latest()->get(),
            'informationLayers' => $informationLayers,
            'referenceRoutes' => $referenceRoutes,
            'corridors' => $corridors,
            'referencePoints' => $referencePoints,
            'stationSpatialReferences' => $stationSpatialReferences,
            'warningStationTelemetryConfigs' => $warningStationTelemetryConfigs,
            'warningStationDevices' => $warningStationDevices,
            'spatialResources' => $this->spatialResourcesForProjects($projectModels),
            'permissions' => $permissions,
            'provinces' => $provinces,
            'canonicalParameters' => Schema::hasTable('canonical_parameters') ? CanonicalParameter::orderBy('domain')->orderBy('field_identity')->get() : collect(),
            'sensorMappingProfiles' => Schema::hasTable('sensor_mapping_profiles') ? SensorMappingProfile::with(['sensor', 'canonicalParameter'])->latest()->get() : collect(),
            'databaseReady' => true,
        ];
    }

    private function spatialResourcesForProjects(Collection $projects): array
    {
        $projectIds = $projects->pluck('id')->all();

        if (empty($projectIds)) {
            return [
                'workspaces' => [],
                'informationLayers' => [],
                'referenceRoutes' => [],
                'corridors' => [],
                'referencePoints' => [],
                'monitoringStations' => [],
                'warningStations' => [],
                'sensors' => [],
                'stationSpatialReferences' => [],
            ];
        }

        $workspaces = GeospatialWorkspace::with('project')
            ->whereIn('project_id', $projectIds)
            ->orderBy('workspace_code')
            ->get()
            ->map(fn (GeospatialWorkspace $workspace) => [
                'id' => $workspace->id,
                'project_id' => $workspace->project_id,
                'workspace_code' => $workspace->workspace_code,
                'name' => $workspace->name,
                'latitude' => $workspace->latitude,
                'longitude' => $workspace->longitude,
                'status' => $workspace->status,
                'basemap' => [
                    'provider' => $workspace->basemap_provider ?? 'OpenStreetMap',
                    'tile_url' => $workspace->basemap_tile_url,
                    'default_zoom' => $workspace->default_zoom ?? 5,
                    'bounds' => $workspace->map_bounds ?? [],
                ],
            ])
            ->values()
            ->all();

        return [
            'workspaces' => $workspaces,
            'informationLayers' => Schema::hasTable('spatial_information_layers')
                ? SpatialInformationLayer::whereIn('project_id', $projectIds)->orderBy('sort_order')->orderBy('layer_code')->get()->map(fn ($layer) => [
                    'id' => $layer->id,
                    'project_id' => $layer->project_id,
                    'workspace_id' => $layer->workspace_id,
                    'layer_code' => $layer->layer_code,
                    'name' => $layer->name,
                    'layer_type' => $layer->layer_type,
                    'source_url' => $layer->source_url,
                    'layer_payload' => $layer->layer_payload ?? [],
                    'style_color' => $layer->style_color,
                    'visible_by_default' => $layer->visible_by_default,
                    'status' => $layer->status,
                ])->values()->all()
                : [],
            'referenceRoutes' => Schema::hasTable('reference_routes')
                ? ReferenceRoute::with(['project', 'workspace'])->whereIn('project_id', $projectIds)->orderBy('route_code')->get()->map(fn ($route) => $this->routeRow($route))->values()->all()
                : [],
            'corridors' => Schema::hasTable('corridor_monitorings')
                ? CorridorMonitoring::with(['project', 'workspace', 'referenceRoute'])->whereIn('project_id', $projectIds)->orderBy('corridor_code')->get()->map(fn ($corridor) => $this->corridorRow($corridor))->values()->all()
                : [],
            'referencePoints' => Schema::hasTable('reference_points')
                ? ReferencePoint::whereIn('project_id', $projectIds)->orderBy('point_code')->get()->map(fn ($point) => [
                    'id' => $point->id,
                    'project_id' => $point->project_id,
                    'workspace_id' => $point->workspace_id,
                    'corridor_id' => $point->corridor_id,
                    'reference_route_id' => $point->reference_route_id,
                    'point_code' => $point->point_code,
                    'name' => $point->name,
                    'point_type' => $point->point_type,
                    'latitude' => $point->latitude,
                    'longitude' => $point->longitude,
                    'status' => $point->status,
                ])->values()->all()
                : [],
            'monitoringStations' => Schema::hasTable('monitoring_stations')
                ? MonitoringStation::whereIn('project_id', $projectIds)->orderBy('station_code')->get()->map(fn (MonitoringStation $station) => [
                    'id' => $station->id,
                    'project_id' => $station->project_id,
                    'workspace_id' => $station->workspace_id,
                    'corridor_id' => $station->corridor_id,
                    'station_code' => $station->station_code,
                    'name' => $station->name,
                    'station_type' => $station->station_type,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'coordinate' => $station->coordinate,
                    'status' => $station->status,
                    'connectivity_status' => $station->connectivity_status,
                ])->values()->all()
                : [],
            'warningStations' => Schema::hasTable('warning_stations')
                ? WarningStation::whereIn('project_id', $projectIds)->orderBy('station_code')->get()->map(fn (WarningStation $station) => [
                    'id' => $station->id,
                    'project_id' => $station->project_id,
                    'workspace_id' => $station->workspace_id,
                    'station_code' => $station->station_code,
                    'name' => $station->name,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'coordinate' => $station->coordinate,
                    'status' => $station->status,
                    'controller_status' => $station->controller_status,
                ])->values()->all()
                : [],
            'sensors' => Schema::hasTable('sensors')
                ? Sensor::with(['workspace', 'monitoringStation', 'warningStation', 'dataLogger'])
                    ->where(function ($query) use ($projectIds) {
                        $query
                            ->whereHas('workspace', fn ($workspace) => $workspace->whereIn('project_id', $projectIds))
                            ->orWhereHas('monitoringStation', fn ($station) => $station->whereIn('project_id', $projectIds))
                            ->orWhereHas('warningStation', fn ($station) => $station->whereIn('project_id', $projectIds));
                    })
                    ->orderBy('sensor_code')
                    ->get()
                    ->map(function (Sensor $sensor, int $index) {
                        [$latitude, $longitude] = $this->sensorMapCoordinate($sensor, $index);

                        return [
                            'id' => $sensor->id,
                            'project_id' => $sensor->monitoringStation?->project_id
                                ?? $sensor->warningStation?->project_id
                                ?? $sensor->workspace?->project_id,
                            'workspace_id' => $sensor->workspace_id,
                            'monitoring_station_id' => $sensor->monitoring_station_id,
                            'warning_station_id' => $sensor->warning_station_id,
                            'data_logger_id' => $sensor->data_logger_id,
                            'station_code' => $sensor->monitoringStation?->station_code
                                ?? $sensor->warningStation?->station_code,
                            'logger_code' => $sensor->dataLogger?->logger_code,
                            'sensor_code' => $sensor->sensor_code,
                            'name' => $sensor->sensor_code,
                            'type' => $sensor->type,
                            'parameter' => $sensor->parameter,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'status' => $sensor->status,
                            'alert_level' => $sensor->alert_level,
                        ];
                    })
                    ->values()
                    ->all()
                : [],
            'stationSpatialReferences' => Schema::hasTable('station_spatial_references')
                ? StationSpatialReference::whereIn('project_id', $projectIds)->orderBy('id')->get()->map(fn ($reference) => [
                    'id' => $reference->id,
                    'project_id' => $reference->project_id,
                    'workspace_id' => $reference->workspace_id,
                    'corridor_id' => $reference->corridor_id,
                    'reference_route_id' => $reference->reference_route_id,
                    'reference_point_id' => $reference->reference_point_id,
                    'monitoring_station_id' => $reference->monitoring_station_id,
                    'warning_station_id' => $reference->warning_station_id,
                    'placement_role' => $reference->placement_role,
                    'station_offset' => $reference->station_offset,
                    'status' => $reference->status,
                ])->values()->all()
                : [],
        ];
    }

    private function sensorMapCoordinate(Sensor $sensor, int $index): array
    {
        $baseLatitude = $sensor->monitoringStation?->latitude
            ?? $sensor->warningStation?->latitude
            ?? $sensor->workspace?->latitude;
        $baseLongitude = $sensor->monitoringStation?->longitude
            ?? $sensor->warningStation?->longitude
            ?? $sensor->workspace?->longitude;

        if ($baseLatitude === null || $baseLongitude === null) {
            return [null, null];
        }

        $angle = deg2rad(($index % 8) * 45);
        $distance = 0.0010 + (floor($index / 8) * 0.0005);

        return [
            round((float) $baseLatitude + (cos($angle) * $distance), 6),
            round((float) $baseLongitude + (sin($angle) * $distance), 6),
        ];
    }

    private function routeRow(ReferenceRoute $route): array
    {
        return [
            'db_id' => $route->id,
            'project_db_id' => $route->project_id,
            'workspace_db_id' => $route->workspace_id,
            'id' => $route->route_code,
            'project_id' => $route->project?->project_code,
            'workspace_id' => $route->workspace?->workspace_code,
            'name' => $route->name,
            'route_type' => $route->route_type,
            'path_coordinates' => $route->path_coordinates ?? [],
            'status' => $route->status,
            'notes' => $route->notes,
        ];
    }

    private function corridorRow(CorridorMonitoring $corridor): array
    {
        return [
            'db_id' => $corridor->id,
            'project_db_id' => $corridor->project_id,
            'workspace_db_id' => $corridor->workspace_id,
            'reference_route_db_id' => $corridor->reference_route_id,
            'id' => $corridor->corridor_code,
            'project_id' => $corridor->project?->project_code,
            'workspace_id' => $corridor->workspace?->workspace_code,
            'reference_route_id' => $corridor->referenceRoute?->route_code,
            'name' => $corridor->name,
            'path_coordinates' => $corridor->path_coordinates ?? [],
            'status' => $corridor->status,
            'status_metadata' => $corridor->status_metadata ?? [],
            'notes' => $corridor->notes,
        ];
    }

    private function provinces(): array
    {
        if (Schema::hasTable('provinces')) {
            return Province::query()
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        return config('indonesia.provinces') ?? [];
    }

    private function dataLoggersFromModels($dataLoggers)
    {
        return collect($dataLoggers)->map(fn (DataLogger $logger) => [
            'db_id' => $logger->id,
            'id' => $logger->logger_code,
            'monitoring_station_db_id' => $logger->monitoring_station_id,
            'monitoring_station_id' => $logger->monitoringStation?->station_code,
            'serial_number' => $logger->serial_number,
            'logger_model' => $logger->logger_model,
            'vendor' => $logger->vendor,
            'firmware_version' => $logger->firmware_version,
            'device_label' => $logger->device_label,
            'logger_status' => $logger->logger_status,
            'poll_interval_ms' => $logger->poll_interval_ms ?? 2000,
        ]);
    }

    private function connectivityFromModels($connectivity)
    {
        return collect($connectivity)->map(fn (ConnectivityConfig $item) => [
            'db_id' => $item->id,
            'data_logger_db_id' => $item->data_logger_id,
            'id' => $item->connectivity_code,
            'logger_id' => $item->dataLogger?->logger_code,
            'communication_type' => $item->communication_type,
            'protocol' => $item->protocol,
            'host_or_endpoint' => $item->host_or_endpoint,
            'port' => $item->port,
            'topic_or_api_path' => $item->topic_or_api_path,
            'gateway_id' => $item->gateway_id,
            'sim_number' => $item->sim_number,
            'imei' => $item->imei,
            'apn' => $item->apn,
            'connectivity_status' => $item->connectivity_status,
        ]);
    }

    private function credentialsFromModels($credentials)
    {
        return collect($credentials)->map(fn (DeviceCredential $credential) => [
            'db_id' => $credential->id,
            'data_logger_db_id' => $credential->data_logger_id,
            'id' => $credential->credential_code,
            'logger_id' => $credential->dataLogger?->logger_code,
            'device_token' => $credential->device_token,
            'mqtt_username' => $credential->mqtt_username,
            'mqtt_password_hash' => $credential->mqtt_password_hash,
            'certificate_ref' => $credential->certificate_ref,
            'credential_status' => $credential->credential_status,
            'created_at' => optional($credential->created_at)->format('Y-m-d H:i:s'),
            'revoked_at' => optional($credential->revoked_at)->format('Y-m-d H:i:s'),
        ]);
    }

    private function telemetryFromModels($readings)
    {
        return collect($readings)->map(fn (TelemetryReading $reading) => [
            'db_id' => $reading->id,
            'sensor_db_id' => $reading->sensor_id,
            'sensor_id' => $reading->sensor?->sensor_code,
            'monitoring_station_id' => $reading->sensor?->monitoringStation?->station_code,
            'data_logger_db_id' => $reading->data_logger_id,
            'data_logger_id' => $reading->dataLogger?->logger_code,
            'value' => $reading->value,
            'alert_level' => $reading->alert_level,
            'status' => $reading->status,
            'received_at' => optional($reading->received_at ?? $reading->created_at)->diffForHumans(),
            'received_at_input' => optional($reading->received_at)->format('Y-m-d\TH:i'),
        ]);
    }

    private function applyProvinceCoordinates(array $data): array
    {
        if ((! empty($data['latitude']) && ! empty($data['longitude'])) || ! Schema::hasTable('provinces')) {
            return $data;
        }

        $province = Province::where('name', $data['province'])->first();

        if ($province) {
            $data['latitude'] = $province->latitude;
            $data['longitude'] = $province->longitude;
        }

        return $data;
    }

    private function applyParsedCoordinate(array $data): array
    {
        if ((! empty($data['latitude']) && ! empty($data['longitude'])) || empty($data['coordinate'])) {
            return $data;
        }

        $parts = array_map('trim', explode(',', $data['coordinate']));

        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $data['latitude'] = $parts[0];
            $data['longitude'] = $parts[1];
        }

        return $data;
    }
}
