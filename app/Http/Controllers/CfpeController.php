<?php

namespace App\Http\Controllers;

use App\Models\CorridorMonitoring;
use App\Models\Project;
use App\Models\ReferenceRoute;
use App\Models\ReferencePoint;
use App\Models\GeospatialWorkspace;
use App\Models\SpatialInformationLayer;
use App\Models\StationFunctionConfiguration;
use App\Services\CfpeRouteImportService;
use App\Services\CfpeCalculationService;
use App\Services\GpkgImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                    'point_code' => $p->point_code,
                    'bm_id' => $p->bm_id,
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
        $monitoringStations = \App\Models\MonitoringStation::whereNotNull('latitude')
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
            ]);

        $warningStations = \App\Models\WarningStation::whereNotNull('latitude')
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
            ]);

        return response()->json([
            'routes' => $routes,
            'corridors' => $corridors,
            'information_layers' => $layers,
            'monitoring_stations' => $monitoringStations,
            'warning_stations' => $warningStations,
        ]);
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
