<?php

namespace App\Http\Controllers;

use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\StationFunctionConfiguration;
use App\Models\TdeMatrixVersion;
use App\Services\AuthorizationService;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        $availableMatrices = $this->matricesForStation($selectedStation);
        $currentMatrixId = $this->currentMatrixId($selectedStation);

        return view('modules.tde-forecast.index', [
            'stations' => $stations,
            'selectedStation' => $selectedStation,
            'runtime' => $runtime,
            'tde' => $tde,
            'availableMatrices' => $availableMatrices,
            'currentMatrixId' => $currentMatrixId,
            'operationsTitle' => 'TDE Forecast',
        ]);
    }

    /**
     * Sambungkan Matrix TDE ke station langsung dari halaman TDE Forecast.
     * Hanya mengubah tde_matrix_version_id pada konfigurasi TDE, mempertahankan
     * data_window & field lain yang sudah ada.
     */
    public function attachMatrix(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'station_id' => ['required', 'integer'],
            'tde_matrix_version_id' => ['required', 'integer', 'exists:tde_matrix_versions,id'],
        ]);

        $projectQuery = Project::query();
        $this->authorization->scopeProjectsForUser($request->user(), $projectQuery);
        $projectIds = $projectQuery->pluck('id');

        $station = MonitoringStation::with(['project', 'workspace.project'])
            ->whereIn('project_id', $projectIds)
            ->findOrFail((int) $data['station_id']);

        // pastikan matriks tersedia untuk station (global atau sesuai project)
        abort_unless(
            collect($this->matricesForStation($station))->contains(fn ($m) => (int) $m['id'] === (int) $data['tde_matrix_version_id']),
            403,
            'Matrix TDE tidak tersedia untuk station ini.'
        );

        $projectId = $station->project_id ?: $station->workspace?->project_id;
        abort_unless($projectId, 403);

        $config = StationFunctionConfiguration::firstOrNew([
            'monitoring_station_id' => $station->id,
            'function_name' => StationFunctionConfiguration::FUNCTION_TDE,
        ]);

        $existing = $config->configuration ?? [];
        $existing['data_window'] = $existing['data_window'] ?? ['value' => 30, 'unit' => 'minutes'];
        $existing['tde_matrix_version_id'] = (int) $data['tde_matrix_version_id'];
        $existing['client_configurable'] = ['data_window', 'tde_matrix_version_id'];

        $config->project_id = $config->project_id ?: $projectId;
        $config->reading_method = $config->reading_method ?: 'instantaneous';
        $config->configuration = $existing;
        $config->status = $config->status ?: 'active';
        $config->save();

        return redirect()
            ->route('tde-forecast.index', ['station_id' => $station->id])
            ->with('message', 'Matrix TDE berhasil disambungkan ke station.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matricesForStation(?MonitoringStation $station): array
    {
        if (! $station) {
            return [];
        }

        $projectId = $station->project_id ?: $station->workspace?->project_id;

        return TdeMatrixVersion::query()
            ->where('status', 'active')
            ->where(function ($query) use ($projectId) {
                $query->whereNull('project_id');
                if ($projectId) {
                    $query->orWhere('project_id', $projectId);
                }
            })
            ->orderBy('matrix_code')
            ->get()
            ->map(fn (TdeMatrixVersion $m) => [
                'id' => $m->id,
                'matrix_code' => $m->matrix_code,
                'name' => $m->name,
                'version_label' => $m->version_label,
                'scope' => $m->project_id ? 'project' : 'global',
            ])
            ->values()
            ->all();
    }

    private function currentMatrixId(?MonitoringStation $station): ?int
    {
        if (! $station) {
            return null;
        }

        $config = $station->functionConfigurations
            ->firstWhere('function_name', StationFunctionConfiguration::FUNCTION_TDE)
            ?? StationFunctionConfiguration::where('monitoring_station_id', $station->id)
                ->where('function_name', StationFunctionConfiguration::FUNCTION_TDE)
                ->first();

        $id = $config?->configuration['tde_matrix_version_id'] ?? null;

        return $id ? (int) $id : null;
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
