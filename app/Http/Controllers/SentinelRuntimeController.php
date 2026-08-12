<?php

namespace App\Http\Controllers;

use App\Models\MonitoringStation;
use App\Models\Project;
use App\Services\AuthorizationService;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SentinelRuntimeController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly SentinelRuntimeReadService $runtime
    ) {
    }

    public function project(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        return response()->json($this->runtime->projectRuntime($project, $this->freshSeconds($request)));
    }

    public function station(Request $request, MonitoringStation $station): JsonResponse
    {
        $this->authorizeStation($request, $station);

        return response()->json($this->runtime->stationRuntime($station, $this->freshSeconds($request)));
    }

    public function latest(Request $request, MonitoringStation $station): JsonResponse
    {
        $this->authorizeStation($request, $station);

        return response()->json($this->runtime->latestReadings($station, $this->freshSeconds($request)));
    }

    public function timeSeries(Request $request, MonitoringStation $station): JsonResponse
    {
        $this->authorizeStation($request, $station);

        $filters = $request->validate([
            'sensor_id' => ['nullable', 'integer'],
            'sensor_code' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        return response()->json($this->runtime->timeSeries($station, $filters));
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $project), 403);
    }

    private function authorizeStation(Request $request, MonitoringStation $station): void
    {
        abort_unless($this->authorization->canAccessMonitoringStation($request->user(), $station), 403);
    }

    private function freshSeconds(Request $request): int
    {
        return min(max((int) $request->query('fresh_seconds', 300), 30), 86400);
    }
}
