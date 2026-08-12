<?php

namespace App\Http\Controllers;

use App\Models\HydrometHazardClassification;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\StationFunctionConfiguration;
use App\Services\AuthorizationService;
use App\Services\ClientFunctionConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientFunctionConfigurationController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly ClientFunctionConfigurationService $functionConfigurations
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeClient($request);

        $projects = Project::query()->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $projects);
        $projectIds = $projects->pluck('id');

        $stations = MonitoringStation::with([
            'project',
            'workspace.project',
            'corridor',
            'sensors.mappingProfile.canonicalParameter',
            'spatialReferences',
            'functionConfigurations',
        ])
            ->whereIn('project_id', $projectIds)
            ->orderBy('station_code')
            ->get();

        return view('modules.client-operations.function-station-select', [
            'stations' => $this->functionConfigurations->stationRows($stations),
            'operationsTitle' => 'Function Configuration',
        ]);
    }

    public function station(Request $request, MonitoringStation $station): View
    {
        $this->authorizeStation($request, $station);

        return view('modules.client-operations.function-configuration', [
            'station' => $station,
            'context' => $this->functionConfigurations->configurationContext($station),
            'operationsTitle' => 'Function Configuration',
        ]);
    }

    public function store(Request $request, MonitoringStation $station, string $function): RedirectResponse
    {
        $this->authorizeStation($request, $station);
        abort_unless(in_array($function, StationFunctionConfiguration::supportedFunctions(), true), 404);

        $data = $this->validatedPayload($request, $function);
        $this->functionConfigurations->createOrUpdate($station, $function, $data);

        return redirect()
            ->route('client-operations.function-configuration.stations.show', $station)
            ->with('message', $function.' configuration saved.');
    }

    private function authorizeClient(Request $request): void
    {
        abort_unless($request->user()?->isClientUser(), 403);
    }

    private function authorizeStation(Request $request, MonitoringStation $station): void
    {
        $station->loadMissing(['project', 'workspace.project']);
        $project = $station->project ?: $station->workspace?->project;

        $this->authorizeClient($request);
        abort_unless($project && $this->authorization->canAccessProject($request->user(), $project), 403);
    }

    private function validatedPayload(Request $request, string $function): array
    {
        $base = [
            'reading_method' => ['required', Rule::in(HydrometHazardClassification::supportedReadingMethods())],
            'status' => ['nullable', Rule::in(['draft', 'active', 'inactive'])],
            'validate' => ['nullable', 'boolean'],
            'activate' => ['nullable', 'boolean'],
        ];

        $rules = match ($function) {
            StationFunctionConfiguration::FUNCTION_TDE => array_merge($base, [
                'data_window_value' => ['required', 'integer', 'min:1', 'max:10080'],
                'data_window_unit' => ['required', Rule::in(['minutes', 'hours', 'days'])],
            ]),
            StationFunctionConfiguration::FUNCTION_DISCHARGE => array_merge($base, [
                'cross_sectional_area' => ['nullable', 'numeric', 'min:0'],
                'manning_n' => ['nullable', 'numeric', 'min:0'],
                'coefficient_cd' => ['nullable', 'numeric', 'min:0'],
                'unit' => ['required', 'string', 'max:30'],
                'calculation_parameters' => ['nullable', 'array'],
            ]),
            StationFunctionConfiguration::FUNCTION_CFPE => array_merge($base, [
                'reference_route_id' => ['required', 'exists:reference_routes,id'],
                'reference_bm_id' => ['nullable', 'exists:reference_points,id'],
                'offset_direction' => ['required', Rule::in(['left', 'right', 'upstream', 'downstream', 'centerline'])],
                'offset_distance' => ['nullable', 'numeric', 'min:0'],
                'station_ground_zero_chainage' => ['nullable', 'numeric', 'min:0'],
                'uncertainty_factor' => ['nullable', 'numeric', 'min:0'],
            ]),
            default => abort(404),
        };

        return $request->validate($rules);
    }
}
