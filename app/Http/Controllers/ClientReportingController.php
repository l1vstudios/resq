<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AuthorizationService;
use App\Services\ClientReportingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ClientReportingController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly ClientReportingService $reports
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeClient($request);

        $projects = Project::with(['corridors', 'monitoringStations.sensors'])
            ->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $projects);
        $projects = $projects->get();
        $activeProject = $projects->firstWhere('id', (int) $request->query('project_id')) ?: $projects->first();

        return view('modules.client-operations.reporting', [
            'projects' => $projects,
            'activeProject' => $activeProject,
            'targetOptions' => $activeProject ? $this->reports->targetOptions($activeProject) : ['corridors' => [], 'stations' => []],
            'preselectedTargetType' => $request->query('target_type'),
            'preselectedTargetId' => $request->query('target_id'),
        ]);
    }

    public function generate(Request $request): Response
    {
        $this->authorizeClient($request);

        $data = $request->validate([
            'target_type' => ['required', Rule::in([ClientReportingService::TARGET_CORRIDOR, ClientReportingService::TARGET_STATION])],
            'target_id' => ['required', 'integer'],
            'parameter' => ['nullable', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'output' => ['required', Rule::in(['print', 'csv', 'excel'])],
        ]);

        $target = $this->reports->target($data['target_type'], (int) $data['target_id']);
        $projectId = $this->reports->targetProjectId($target, $data['target_type']);
        abort_unless($projectId && $this->authorization->canAccessProject($request->user(), $projectId), 403);

        $report = $this->reports->prepare($data);

        if ($data['output'] === 'print') {
            return $this->reports->printableResponse($report);
        }

        return $this->reports->csvResponse($report, $data['output']);
    }

    private function authorizeClient(Request $request): void
    {
        abort_unless($request->user()?->isClientUser(), 403);
    }
}
