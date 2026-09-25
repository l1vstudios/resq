<?php

namespace App\Http\Controllers;

use App\Models\CorridorMonitoring;
use App\Models\Project;
use App\Models\ReferenceRoute;
use App\Models\ReferencePoint;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Sensor;
use App\Models\SpatialInformationLayer;
use App\Models\StationFunctionConfiguration;
use App\Models\WarningStation;
use App\Services\CfpeRouteImportService;
use App\Services\CfpeCalculationService;
use App\Services\GpkgImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CfpeController extends Controller
{
    public function __construct(
        private readonly CfpeRouteImportService $importService,
        private readonly CfpeCalculationService $calculationService,
        private readonly GpkgImportService $gpkgService
    ) {}

    // GET /cfpe - shows the geospatial workspace with CFPE
    public function index(): View
    {
        $projects = Project::with(['workspaces', 'referenceRoutes' => fn ($q) => $q->select(['id', 'project_id', 'workspace_id', 'route_code', 'name', 'route_type', 'status'])])->get();
        $routes = collect(); // Routes loaded via /cfpe/map-data API
        $workspaces = GeospatialWorkspace::all();
        $corridors = collect();
        $informationLayers = collect();

        return view('modules.cfpe.index', compact('projects', 'routes', 'workspaces', 'corridors', 'informationLayers'));
    }

    // POST /cfpe/import-csv - imports route CSV
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $result = $this->importService->import(
            $path,
            (int) $request->input('project_id'),
            $request->input('workspace_id') ? (int) $request->input('workspace_id') : null
        );

        return response()->json(['success' => true, 'summary' => $result]);
    }

    // POST /cfpe/import-gpkg - imports GPKG file (corridor or information layer)
    public function importGpkg(Request $request): JsonResponse
    {
        $request->validate([
            'gpkg_file' => ['required', 'file'],
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
            'import_type' => ['required', 'in:corridor,information_layer'],
            'layer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('gpkg_file');
        $path = $file->getRealPath();
        $projectId = (int) $request->input('project_id');
        $workspaceId = $request->input('workspace_id') ? (int) $request->input('workspace_id') : null;
        $importType = $request->input('import_type');

        if ($importType === 'corridor') {
            if (!$workspaceId) {
                return response()->json(['success' => false, 'message' => 'Workspace ID diperlukan untuk import corridor.'], 422);
            }
            $result = $this->gpkgService->importCorridor($path, $projectId, $workspaceId);
        } else {
            $layerName = $request->input('layer_name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $result = $this->gpkgService->importInformationLayer($path, $projectId, $workspaceId, $layerName);
        }

        return response()->json($result);
    }

    // POST /cfpe/calculate - runs CFPE calculation
    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference_route_id' => ['required', 'exists:reference_routes,id'],
            'station_ground_zero_chainage' => ['required', 'numeric', 'min:0'],
            'flow_velocity' => ['required', 'numeric', 'min:0.01'],
            'uncertainty_factor' => ['required', 'numeric', 'min:0', 'max:1'],
            'propagation_direction' => ['required', 'in:downstream,upstream'],
        ]);

        $results = $this->calculationService->calculate(
            (int) $data['reference_route_id'],
            (float) $data['station_ground_zero_chainage'],
            (float) $data['flow_velocity'],
            (float) $data['uncertainty_factor'],
            $data['propagation_direction']
        );

        return response()->json([
            'success' => true,
            'input' => $data,
            'results' => $results,
        ]);
    }

    // GET /cfpe/routes/{route}/points - get reference points for a route
    public function routePoints(ReferenceRoute $route): JsonResponse
    {
        $points = $route->referencePoints()
            ->whereNotNull('chainage')
            ->orderBy('chainage')
            ->get()
            ->map(fn (ReferencePoint $point) => [
                'id' => $point->id,
                'point_code' => $point->point_code,
                'bm_id' => $point->bm_id,
                'name' => $point->name,
                'chainage' => (float) $point->chainage,
                'latitude' => (float) $point->latitude,
                'longitude' => (float) $point->longitude,
                'segment_name' => $point->segment_name,
            ]);

        return response()->json(['points' => $points]);
    }

    public function updateMapPoint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:reference_point,monitoring_station,warning_station,sensor'],
            'id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $model = match ($data['type']) {
            'reference_point' => ReferencePoint::findOrFail($data['id']),
            'monitoring_station' => MonitoringStation::findOrFail($data['id']),
            'warning_station' => WarningStation::findOrFail($data['id']),
            'sensor' => Sensor::findOrFail($data['id']),
        };

        $coordinate = sprintf('%.7F, %.7F', (float) $data['latitude'], (float) $data['longitude']);
        $model->forceFill([
            'coordinate' => $coordinate,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ])->save();

        return response()->json([
            'success' => true,
            'coordinate' => $coordinate,
            'latitude' => (float) $model->latitude,
            'longitude' => (float) $model->longitude,
        ]);
    }

    // GET /cfpe/map-data - get all spatial data for the map
    public function mapData(Request $request): JsonResponse
    {
        $projectId = $request->input('project_id');
        $workspaceId = $request->input('workspace_id');

        $routesQuery = ReferenceRoute::with('referencePoints');
        $corridorsQuery = CorridorMonitoring::query();
        $layersQuery = SpatialInformationLayer::where('status', 'Active');

        if ($workspaceId) {
            $routesQuery->where('workspace_id', $workspaceId);
            $corridorsQuery->where('workspace_id', $workspaceId);
            $layersQuery->where('workspace_id', $workspaceId);
        } elseif ($projectId) {
            $routesQuery->where('project_id', $projectId);
            $corridorsQuery->where('project_id', $projectId);
            $layersQuery->where('project_id', $projectId);
        }

        $routes = $routesQuery->get()->map(fn (ReferenceRoute $route) => [
            'id' => $route->id,
            'route_code' => $route->route_code,
            'name' => $route->name,
            'route_type' => $route->route_type,
            'path_coordinates' => $route->path_coordinates,
            'total_length' => $route->total_length,
            'corridor_code' => $route->corridor_code,
            'points' => $route->referencePoints
                ->filter(fn ($p) => $p->latitude && $p->longitude)
                ->map(fn (ReferencePoint $p) => [
                    'id' => $p->id,
                    'reference_route_id' => $p->reference_route_id,
                    'point_code' => $p->point_code,
                    'bm_id' => $p->bm_id,
                    'name' => $p->name,
                    'chainage' => $p->chainage ? (float) $p->chainage : null,
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'segment_name' => $p->segment_name,
                ])->values(),
        ]);

        $corridors = $corridorsQuery->get()->map(fn (CorridorMonitoring $c) => [
            'id' => $c->id,
            'corridor_code' => $c->corridor_code,
            'name' => $c->name,
            'path_coordinates' => $c->path_coordinates,
            'status' => $c->status,
        ]);

        $layers = $layersQuery->get()->map(fn (SpatialInformationLayer $layer) => [
            'id' => $layer->id,
            'layer_code' => $layer->layer_code,
            'name' => $layer->name,
            'layer_type' => $layer->layer_type,
            'layer_payload' => $layer->layer_payload,
            'style_color' => $layer->style_color,
            'visible_by_default' => $layer->visible_by_default,
        ]);

        // Monitoring & Warning Stations
        $monitoringStations = MonitoringStation::with('warningStations')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($workspaceId, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->when($projectId && !$workspaceId, fn ($q) => $q->where('project_id', $projectId))
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'station_code' => $s->station_code,
                'name' => $s->name,
                'latitude' => (float) $s->latitude,
                'longitude' => (float) $s->longitude,
                'status' => $s->status,
                'warning_stations' => $s->warningStations->map(fn (WarningStation $warningStation) => [
                    'id' => $warningStation->id,
                    'station_code' => $warningStation->station_code,
                    'name' => $warningStation->name,
                    'zone_id' => $warningStation->zone_id,
                    'status' => $warningStation->status,
                    'controller_status' => $warningStation->controller_status,
                ])->values(),
            ]);

        $presetLookup = $this->activePresetLookup();

        $warningStations = WarningStation::with(['sensors.dataLogger', 'sensors.mappingProfiles.canonicalParameter'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($workspaceId, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->when($projectId && !$workspaceId, fn ($q) => $q->where('project_id', $projectId))
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'station_code' => $s->station_code,
                'name' => $s->name,
                'latitude' => (float) $s->latitude,
                'longitude' => (float) $s->longitude,
                'status' => $s->status,
                'data_loggers' => $s->sensors
                    ->groupBy(fn ($sensor) => $sensor->dataLogger?->logger_code ?: 'Tanpa Data Logger')
                    ->map(fn ($sensors, string $loggerCode) => [
                        'logger_code' => $loggerCode,
                        'logger_status' => $sensors->first()?->dataLogger?->logger_status,
                        'active_presets' => $sensors
                            ->flatMap(fn ($sensor) => $this->activePresetsForSensor($sensor, $presetLookup))
                            ->unique(fn (array $preset) => $preset['key'])
                            ->values(),
                        'sensors' => $sensors->map(fn ($sensor) => [
                            'id' => $sensor->id,
                            'sensor_code' => $sensor->sensor_code,
                            'name' => $sensor->parameter ?: $sensor->sensor_code,
                            'type' => $sensor->type,
                            'parameter' => $sensor->parameter,
                            'status' => $sensor->status,
                            'alert_level' => $sensor->alert_level,
                            'active_presets' => $this->activePresetsForSensor($sensor, $presetLookup),
                        ])->values(),
                    ])
                    ->values(),
                'sensors' => $s->sensors->map(fn ($sensor) => [
                    'id' => $sensor->id,
                    'sensor_code' => $sensor->sensor_code,
                    'name' => $sensor->parameter ?: $sensor->sensor_code,
                    'type' => $sensor->type,
                    'parameter' => $sensor->parameter,
                    'status' => $sensor->status,
                    'alert_level' => $sensor->alert_level,
                    'active_presets' => $this->activePresetsForSensor($sensor, $presetLookup),
                ])->values(),
            ]);

        $sensors = Sensor::with(['monitoringStation', 'warningStation', 'dataLogger'])
            ->when($workspaceId, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->when($projectId && !$workspaceId, fn ($q) => $q->whereHas('workspace', fn ($workspace) => $workspace->where('project_id', $projectId)))
            ->get()
            ->map(function (Sensor $sensor, int $index) {
                [$latitude, $longitude] = $this->sensorMapCoordinate($sensor, $index);

                return [
                    'id' => $sensor->id,
                    'sensor_code' => $sensor->sensor_code,
                    'name' => $sensor->parameter ?: $sensor->sensor_code,
                    'type' => $sensor->type,
                    'parameter' => $sensor->parameter,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status' => $sensor->status,
                    'alert_level' => $sensor->alert_level,
                    'monitoring_station_id' => $sensor->monitoring_station_id,
                    'monitoring_station_code' => $sensor->monitoringStation?->station_code,
                    'warning_station_id' => $sensor->warning_station_id,
                    'warning_station_code' => $sensor->warningStation?->station_code,
                    'data_logger_id' => $sensor->data_logger_id,
                    'data_logger_code' => $sensor->dataLogger?->logger_code,
                ];
            })
            ->filter(fn (array $sensor) => $sensor['latitude'] !== null && $sensor['longitude'] !== null)
            ->values();

        return response()->json([
            'routes' => $routes,
            'corridors' => $corridors,
            'information_layers' => $layers,
            'monitoring_stations' => $monitoringStations,
            'warning_stations' => $warningStations,
            'sensors' => $sensors,
        ]);
    }

    private function activePresetLookup(): array
    {
        if (! Schema::hasTable('sensor_mapping_presets') || ! Schema::hasTable('sensor_mapping_preset_items')) {
            return [];
        }

        $hasPresetItemProtocol = Schema::hasColumn('sensor_mapping_preset_items', 'function_code');
        $columns = [
            'sensor_mapping_presets.preset_key',
            'sensor_mapping_presets.label',
            'sensor_mapping_presets.manufacturer',
            'sensor_mapping_presets.device_model',
            'sensor_mapping_preset_items.source_parameter',
            'sensor_mapping_preset_items.source_unit',
            'sensor_mapping_preset_items.register_offset',
            'canonical_parameters.field_identity',
        ];

        if ($hasPresetItemProtocol) {
            $columns[] = 'sensor_mapping_preset_items.function_code';
        }

        $items = DB::table('sensor_mapping_preset_items')
            ->join('sensor_mapping_presets', 'sensor_mapping_presets.id', '=', 'sensor_mapping_preset_items.sensor_mapping_preset_id')
            ->leftJoin('canonical_parameters', 'canonical_parameters.id', '=', 'sensor_mapping_preset_items.canonical_parameter_id')
            ->where('sensor_mapping_presets.status', 'active')
            ->orderBy('sensor_mapping_preset_items.sort_order')
            ->orderBy('sensor_mapping_preset_items.register_offset')
            ->get($columns);

        return $items
            ->groupBy(fn ($item) => $this->presetLookupKey($item->manufacturer, $item->device_model))
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'key' => $first->preset_key,
                    'label' => $first->label,
                    'manufacturer' => $first->manufacturer,
                    'device_model' => $first->device_model,
                    'parameters' => $rows->map(fn ($item) => [
                        'name' => $item->field_identity ?: $item->source_parameter,
                        'source_parameter' => $item->source_parameter,
                        'source_unit' => $item->source_unit,
                        'register_offset' => $item->register_offset,
                        'function_code' => $hasPresetItemProtocol ? $item->function_code : null,
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    private function activePresetsForSensor(Sensor $sensor, array $presetLookup): array
    {
        return $sensor->mappingProfiles
            ->filter(fn ($profile) => strtolower((string) $profile->status) === 'active')
            ->groupBy(fn ($profile) => $this->presetLookupKey($profile->manufacturer, $profile->device_model ?: $profile->profile_code))
            ->map(function ($profiles, string $key) use ($presetLookup) {
                $first = $profiles->first();
                $matchedPreset = $presetLookup[$this->presetLookupKey($first->manufacturer, $first->device_model)] ?? null;

                return [
                    'key' => $matchedPreset['key'] ?? $key,
                    'label' => $matchedPreset['label'] ?? trim(collect([$first->manufacturer, $first->device_model ?: $first->profile_code])->filter()->join(' ')),
                    'manufacturer' => $matchedPreset['manufacturer'] ?? $first->manufacturer,
                    'device_model' => $matchedPreset['device_model'] ?? $first->device_model,
                    'parameters' => $matchedPreset['parameters'] ?? $profiles
                        ->map(fn ($profile) => [
                            'name' => $profile->canonicalParameter?->field_identity ?: $profile->source_parameter,
                            'source_parameter' => $profile->source_parameter,
                            'source_unit' => $profile->source_unit,
                            'register_offset' => $profile->register_address,
                            'function_code' => $profile->function_code,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function presetLookupKey(?string $manufacturer, ?string $deviceModel): string
    {
        return strtolower(trim(($manufacturer ?: '-') . '|' . ($deviceModel ?: '-')));
    }

    private function sensorMapCoordinate(Sensor $sensor, int $index): array
    {
        if ($sensor->latitude !== null && $sensor->longitude !== null) {
            return [(float) $sensor->latitude, (float) $sensor->longitude];
        }

        $baseLatitude = $sensor->warningStation?->latitude
            ?? $sensor->monitoringStation?->latitude
            ?? $sensor->workspace?->latitude;
        $baseLongitude = $sensor->warningStation?->longitude
            ?? $sensor->monitoringStation?->longitude
            ?? $sensor->workspace?->longitude;

        if ($baseLatitude === null || $baseLongitude === null) {
            return [null, null];
        }

        $angle = deg2rad(($index % 8) * 45);
        $distance = 0.0010 + (floor($index / 8) * 0.0005);

        return [
            round((float) $baseLatitude + (cos($angle) * $distance), 7),
            round((float) $baseLongitude + (sin($angle) * $distance), 7),
        ];
    }

    // GET /cfpe/workspace-data/{workspace} - get all spatial data for specific workspace
    public function workspaceData(GeospatialWorkspace $workspace): JsonResponse
    {
        $workspace->load([
            'referenceRoutes.referencePoints',
            'corridors',
            'monitoringStations',
            'warningStations',
            'informationLayers',
        ]);

        $routes = $workspace->referenceRoutes->map(fn (ReferenceRoute $route) => [
            'id' => $route->id,
            'route_code' => $route->route_code,
            'name' => $route->name,
            'route_type' => $route->route_type,
            'path_coordinates' => $route->path_coordinates,
            'total_length' => $route->total_length,
            'points' => $route->referencePoints
                ->filter(fn ($p) => $p->latitude && $p->longitude)
                ->map(fn (ReferencePoint $p) => [
                    'id' => $p->id,
                    'point_code' => $p->point_code,
                    'bm_id' => $p->bm_id,
                    'chainage' => $p->chainage ? (float) $p->chainage : null,
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'segment_name' => $p->segment_name,
                ])->values(),
        ]);

        return response()->json([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'map_bounds' => $workspace->map_bounds,
                'default_zoom' => $workspace->default_zoom,
                'basemap_tile_url' => $workspace->basemap_tile_url,
            ],
            'routes' => $routes,
            'corridors' => $workspace->corridors->map(fn ($c) => [
                'id' => $c->id,
                'corridor_code' => $c->corridor_code,
                'name' => $c->name,
                'path_coordinates' => $c->path_coordinates,
                'status' => $c->status,
            ]),
            'information_layers' => $workspace->informationLayers->map(fn ($l) => [
                'id' => $l->id,
                'layer_code' => $l->layer_code,
                'name' => $l->name,
                'layer_type' => $l->layer_type,
                'layer_payload' => $l->layer_payload,
                'style_color' => $l->style_color,
                'visible_by_default' => $l->visible_by_default,
            ]),
            'monitoring_stations' => $workspace->monitoringStations->map(fn ($s) => [
                'id' => $s->id,
                'station_code' => $s->station_code,
                'name' => $s->name,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
            ]),
            'warning_stations' => $workspace->warningStations->map(fn ($s) => [
                'id' => $s->id,
                'station_code' => $s->station_code,
                'name' => $s->name,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
            ]),
        ]);
    }
}
