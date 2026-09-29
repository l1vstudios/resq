<?php

namespace App\Http\Controllers;

use App\Models\MonitoringStation;
use App\Models\Project;
use App\Services\AuthorizationService;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TdeForecastController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly SentinelRuntimeReadService $runtime
    ) {
    }

    public function index(Request $request): View
    {
        $projectQuery = Project::query()->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $projectQuery);
        $projectIds = $projectQuery->pluck('id');

        $stations = MonitoringStation::with(['project', 'workspace.project', 'corridor'])
            ->whereIn('project_id', $projectIds)
            ->orderBy('station_code')
            ->get();

        $selectedStation = $stations->firstWhere('id', (int) $request->query('station_id')) ?? $stations->first();

        if ($request->filled('station_id')) {
            abort_unless($selectedStation && (int) $selectedStation->id === (int) $request->query('station_id'), 403);
        }

        $runtime = $selectedStation ? $this->runtime->stationRuntime($selectedStation) : null;
        $tde = collect($runtime['analytical_outputs'] ?? [])
            ->firstWhere('function', 'TDE');

        return view('modules.tde-forecast.index', [
            'stations' => $stations,
            'selectedStation' => $selectedStation,
            'runtime' => $runtime,
            'tde' => $tde,
            'operationsTitle' => 'TDE Forecast',
        ]);
    }
}
