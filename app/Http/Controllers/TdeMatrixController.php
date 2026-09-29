<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TdeMatrixVersion;
use App\Services\AuthorizationService;
use App\Services\TdeMatrixImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TdeMatrixController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly TdeMatrixImportService $importer
    ) {}

    public function index(Request $request): View
    {
        $projectQuery = Project::query()->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $projectQuery);
        $projectIds = $projectQuery->pluck('id');

        $matrices = TdeMatrixVersion::with('project')
            ->where(function ($query) use ($projectIds) {
                $query->whereNull('project_id')
                    ->orWhereIn('project_id', $projectIds);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('modules.master-data-tde.index', [
            'matrices' => $matrices,
            'projects' => Project::whereIn('id', $projectIds)->orderBy('project_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $projectQuery = Project::query();
        $this->authorization->scopeProjectsForUser($request->user(), $projectQuery);
        $projectIds = $projectQuery->pluck('id')->all();

        $data = $request->validate([
            'matrix_code' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
            'version_label' => ['nullable', 'string', 'max:80'],
            'project_id' => ['nullable', Rule::in($projectIds)],
            'status' => ['nullable', Rule::in(['draft', 'active', 'inactive'])],
            'matrix_file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        $this->importer->import($request->file('matrix_file'), [
            ...$data,
            'imported_by_user_id' => $request->user()?->id,
            'status' => $data['status'] ?? 'active',
        ]);

        return redirect()
            ->route('master-data-tde.index')
            ->with('message', 'TDE Matrix imported and validated.');
    }

    public function template(): StreamedResponse
    {
        $rows = $this->importer->templateRows();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'tde-matrix-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
