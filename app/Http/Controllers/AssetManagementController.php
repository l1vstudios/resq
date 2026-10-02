<?php

namespace App\Http\Controllers;

use App\Models\AssetDevice;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Services\AuthorizationService;
use App\Services\EpeverXtraMpptScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetManagementController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly EpeverXtraMpptScanner $scanner
    ) {}

    public function index(Request $request): View
    {
        $projectQuery = Project::query()->orderBy('project_code');
        $this->authorization->scopeProjectsForUser($request->user(), $projectQuery);
        $projects = $projectQuery->get();

        $stations = MonitoringStation::with('project')
            ->whereIn('project_id', $projects->pluck('id'))
            ->orderBy('station_code')
            ->get();

        $assets = AssetDevice::with(['project', 'monitoringStation'])
            ->whereIn('project_id', $projects->pluck('id'))
            ->latest()
            ->get();

        return view('modules.asset-management.index', [
            'assets' => $assets,
            'projects' => $projects,
            'stations' => $stations,
            'availablePorts' => $this->scanner->availablePorts(),
            'scanResult' => session('scan_result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedAssetData($request);

        $project = Project::findOrFail($data['project_id']);
        abort_unless($this->authorization->canAccessProject($request->user(), $project)
            && $this->authorization->canMutateAssetRegistry($request->user()), 403);

        if (! empty($data['monitoring_station_id'])) {
            $station = MonitoringStation::findOrFail($data['monitoring_station_id']);
            if ((int) $station->project_id !== (int) $project->id) {
                throw ValidationException::withMessages([
                    'monitoring_station_id' => 'Monitoring station harus berada dalam project yang sama.',
                ]);
            }
        }

        $asset = AssetDevice::updateOrCreate(
            ['asset_code' => $data['asset_code']],
            $data
        );

        return redirect()
            ->route('asset-management.index')
            ->with('message', "Asset {$asset->asset_code} berhasil disimpan.");
    }

    public function show(Request $request, AssetDevice $asset): View
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $asset->project_id), 403);

        return view('modules.asset-management.show', [
            'asset' => $asset->load(['project', 'monitoringStation']),
            'scanUrl' => route('asset-management.scan', $asset, false),
        ]);
    }

    public function scan(Request $request, AssetDevice $asset): JsonResponse|RedirectResponse
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $asset->project_id), 403);

        $result = $this->scanAndStore($asset);

        if ($request->expectsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        return back()->with('scan_result', [
            'ok' => $result['ok'],
            'title' => $result['ok'] ? 'Scan berhasil' : 'Scan gagal',
            'message' => $result['message'],
            'payload' => $result['payload'] ?? null,
        ]);
    }

    public function quickScan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_port' => ['required', 'string', 'max:255'],
            'slave_address' => ['required', 'integer', 'min:1', 'max:247'],
            'baud_rate' => ['required', 'integer', Rule::in([9600, 19200, 38400, 57600, 115200])],
            'timeout_ms' => ['required', 'integer', 'min:300', 'max:10000'],
        ]);

        try {
            $payload = $this->scanner->scan([
                ...$data,
                'data_bits' => 8,
                'parity' => 'none',
                'stop_bits' => 1,
            ]);
        } catch (\Throwable $error) {
            return back()
                ->withInput()
                ->with('scan_result', [
                    'ok' => false,
                    'title' => 'Quick scan gagal',
                    'message' => $error->getMessage(),
                ]);
        }

        return back()
            ->withInput()
            ->with('scan_result', [
                'ok' => true,
                'title' => 'Quick scan berhasil',
                'message' => 'MPPT merespons dari '.$payload['port'].'.',
                'payload' => $payload,
            ]);
    }

    public function destroy(Request $request, AssetDevice $asset): RedirectResponse
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $asset->project_id)
            && $this->authorization->canMutateAssetRegistry($request->user()), 403);

        $asset->delete();

        return back()->with('message', 'Asset berhasil dihapus.');
    }

    private function scanAndStore(AssetDevice $asset): array
    {
        try {
            $payload = $this->scanner->scan($asset->toArray());
            $asset->forceFill([
                'last_scanned_at' => now(),
                'last_scan_status' => 'success',
                'last_scan_message' => 'Scan berhasil.',
                'last_scan_payload' => $payload,
            ])->save();
        } catch (\Throwable $error) {
            $asset->forceFill([
                'last_scanned_at' => now(),
                'last_scan_status' => 'failed',
                'last_scan_message' => $error->getMessage(),
                'last_scan_payload' => null,
            ])->save();

            return [
                'ok' => false,
                'message' => $error->getMessage(),
                'asset' => $this->assetRuntimePayload($asset->fresh(['project', 'monitoringStation'])),
            ];
        }

        return [
            'ok' => true,
            'message' => "Asset {$asset->asset_code} berhasil dibaca.",
            'payload' => $payload,
            'asset' => $this->assetRuntimePayload($asset->fresh(['project', 'monitoringStation'])),
        ];
    }

    private function assetRuntimePayload(AssetDevice $asset): array
    {
        return [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'status' => $asset->status,
            'serial_port' => $asset->serial_port,
            'slave_address' => $asset->slave_address,
            'baud_rate' => $asset->baud_rate,
            'last_scanned_at' => optional($asset->last_scanned_at)->toISOString(),
            'last_scan_status' => $asset->last_scan_status,
            'last_scan_message' => $asset->last_scan_message,
            'last_scan_payload' => $asset->last_scan_payload,
        ];
    }

    private function validatedAssetData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'monitoring_station_id' => ['nullable', 'exists:monitoring_stations,id'],
            'asset_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
            'asset_type' => ['required', Rule::in(['mppt_charge_controller'])],
            'vendor' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'protocol' => ['required', Rule::in(['modbus_rtu'])],
            'serial_port' => ['required', 'string', 'max:255'],
            'slave_address' => ['required', 'integer', 'min:1', 'max:247'],
            'baud_rate' => ['required', 'integer', Rule::in([9600, 19200, 38400, 57600, 115200])],
            'data_bits' => ['required', 'integer', Rule::in([7, 8])],
            'parity' => ['required', Rule::in(['none', 'even', 'odd'])],
            'stop_bits' => ['required', 'integer', Rule::in([1, 2])],
            'timeout_ms' => ['required', 'integer', 'min:300', 'max:10000'],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Maintenance', 'Fault'])],
        ]);
    }
}
