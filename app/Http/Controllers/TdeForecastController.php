<?php

namespace App\Http\Controllers;

use App\Models\MonitoringStation;
use App\Models\Project;
use App\Services\AuthorizationService;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Http\JsonResponse;
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

    /**
     * Endpoint JSON untuk realtime polling TDE Forecast (tanpa reload halaman).
     */
    public function data(Request $request): JsonResponse
    {
        $projectQuery = Project::query();
        $this->authorization->scopeProjectsForUser($request->user(), $projectQuery);
        $projectIds = $projectQuery->pluck('id');

        $station = MonitoringStation::with(['project', 'corridor'])
            ->whereIn('project_id', $projectIds)
            ->find((int) $request->query('station_id'));

        if (! $station) {
            return response()->json([
                'ok' => false,
                'message' => 'Station tidak ditemukan atau tidak dapat diakses.',
            ], 404);
        }

        $runtime = $this->runtime->stationRuntime($station);
        $tde = collect($runtime['analytical_outputs'] ?? [])
            ->firstWhere('function', 'TDE');

        return response()->json([
            'ok' => true,
            'server_time' => now()->toISOString(),
            'station' => [
                'id' => $station->id,
                'station_code' => $station->station_code,
                'name' => $station->name,
            ],
            'tde' => $tde,
        ]);
    }
}
