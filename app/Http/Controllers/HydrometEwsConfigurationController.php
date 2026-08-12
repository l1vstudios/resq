<?php

namespace App\Http\Controllers;

use App\Models\HydrometEwsRelationship;
use App\Models\HydrometHazardClassification;
use App\Models\HydrometWdamConfig;
use App\Models\Project;
use App\Services\AuthorizationService;
use App\Services\HydrometEwsConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HydrometEwsConfigurationController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly HydrometEwsConfigurationService $hydrometEws
    ) {
    }

    public function show(Request $request, HydrometEwsRelationship $relationship): JsonResponse
    {
        $project = Project::findOrFail($relationship->project_id);
        $this->authorizeConfiguration($request, $project);

        return response()->json($this->hydrometEws->relationshipConfiguration($relationship));
    }

    public function storeRelationship(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'corridor_id' => ['required', 'exists:corridor_monitorings,id'],
            'monitoring_station_id' => ['required', 'exists:monitoring_stations,id'],
            'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
            'relationship_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $project = Project::findOrFail($data['project_id']);
        $existing = HydrometEwsRelationship::where('relationship_code', $data['relationship_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeConfiguration($request, $project);

        $relationship = $this->hydrometEws->createOrUpdateRelationship($data);

        return response()->json([
            'ok' => true,
            'relationship' => $this->hydrometEws->relationshipRow($relationship->load(['project', 'corridor', 'monitoringStation', 'warningStation'])),
        ], $existing ? 200 : 201);
    }

    public function storeHazardClassification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hydromet_ews_relationship_id' => ['required', 'exists:hydromet_ews_relationships,id'],
            'sensor_id' => ['nullable', 'exists:sensors,id'],
            'canonical_parameter_id' => ['nullable', 'exists:canonical_parameters,id'],
            'classification_code' => ['required', 'string', 'max:255'],
            'parameter' => ['nullable', 'string', 'max:255'],
            'reading_method' => ['required', Rule::in(HydrometHazardClassification::supportedReadingMethods())],
            'threshold_config' => ['nullable', 'array'],
            'hazard_levels' => ['nullable', 'array'],
            'hazard_levels.WASPADA' => ['nullable'],
            'hazard_levels.SIAGA' => ['nullable'],
            'hazard_levels.AWAS' => ['nullable'],
            'unresolved_business_rules' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
        $relationship = HydrometEwsRelationship::findOrFail($data['hydromet_ews_relationship_id']);
        $project = Project::findOrFail($relationship->project_id);
        $existing = HydrometHazardClassification::where('classification_code', $data['classification_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeConfiguration($request, $project);

        $classification = $this->hydrometEws->createOrUpdateHazardClassification($data);

        return response()->json([
            'ok' => true,
            'classification' => $this->hydrometEws->classificationRow($classification->load(['sensor', 'canonicalParameter'])),
        ], $existing ? 200 : 201);
    }

    public function storeWdamConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hydromet_ews_relationship_id' => ['required', 'exists:hydromet_ews_relationships,id'],
            'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
            'wdam_code' => ['required', 'string', 'max:255'],
            'dashboard_notification_enabled' => ['nullable', 'boolean'],
            'registered_recipients' => ['nullable', 'array'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_provider_ref' => ['nullable', 'string', 'max:255'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_provider_ref' => ['nullable', 'string', 'max:255'],
            'warning_station_assignment_enabled' => ['nullable', 'boolean'],
            'automatic_activation_enabled' => ['nullable', 'boolean'],
            'authority_method' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $relationship = HydrometEwsRelationship::findOrFail($data['hydromet_ews_relationship_id']);
        $project = Project::findOrFail($relationship->project_id);
        $existing = HydrometWdamConfig::where('wdam_code', $data['wdam_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeConfiguration($request, $project);

        $wdam = $this->hydrometEws->createOrUpdateWdamConfig($data);

        return response()->json([
            'ok' => true,
            'wdam' => $this->hydrometEws->wdamRow($wdam->load('warningStation')),
        ], $existing ? 200 : 201);
    }

    private function authorizeConfiguration(Request $request, Project $project): void
    {
        abort_unless($this->authorization->canConfigureHydrometEws($request->user(), $project), 403);
    }
}
