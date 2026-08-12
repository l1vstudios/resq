<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use App\Models\WarningStationTelemetryConfig;
use App\Services\AuthorizationService;
use App\Services\WarningStationDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarningStationDomainController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WarningStationDomainService $warningStations
    ) {
    }

    public function show(Request $request, WarningStation $station): JsonResponse
    {
        abort_unless($this->authorization->canAccessWarningStation($request->user(), $station), 403);

        return response()->json($this->warningStations->stationDomain($station));
    }

    public function storeTelemetryConfig(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warning_station_id' => ['required', 'exists:warning_stations,id'],
            'config_code' => ['required', 'string', 'max:255'],
            'broker_config_ref' => ['nullable', 'string', 'max:255'],
            'protocol' => ['required', 'string', 'max:50'],
            'host_or_endpoint' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'topic' => ['required', 'string', 'max:255'],
            'qos' => ['required', 'integer', 'min:0', 'max:2'],
            'retain' => ['nullable', 'boolean'],
            'credential_ref' => ['nullable', 'string', 'max:255'],
            'connection_status' => ['required', 'string', 'max:50'],
        ]);

        $station = WarningStation::findOrFail($data['warning_station_id']);
        $project = $this->stationProject($station);
        $existing = WarningStationTelemetryConfig::where('config_code', $data['config_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeMutation($request, $project, $existing ? 'edit' : 'create');

        $data['project_id'] = $project->id;
        $data['retain'] = $request->boolean('retain');
        if ($data['connection_status'] === 'connected') {
            $data['last_connected_at'] = now();
        }

        WarningStationTelemetryConfig::updateOrCreate(['config_code' => $data['config_code']], $data);

        return back()->with('message', 'Warning station telemetry config berhasil disimpan.');
    }

    public function storeDevice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warning_station_id' => ['required', 'exists:warning_stations,id'],
            'device_code' => ['required', 'string', 'max:255'],
            'device_type' => ['required', Rule::in([
                WarningStationDevice::TYPE_WSCP,
                WarningStationDevice::TYPE_ASCP,
                WarningStationDevice::TYPE_SIREN,
                WarningStationDevice::TYPE_BEACON,
                WarningStationDevice::TYPE_OTHER_OUTPUT,
            ])],
            'name' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'expected' => ['nullable', 'boolean'],
            'availability_state' => ['nullable', 'string', 'max:50'],
            'health_state' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $station = WarningStation::findOrFail($data['warning_station_id']);
        $project = $this->stationProject($station);
        $existing = WarningStationDevice::where('device_code', $data['device_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        $this->authorizeMutation($request, $project, $existing ? 'edit' : 'create');

        $data['project_id'] = $project->id;
        $data['expected'] = $request->boolean('expected', true);
        $data['availability_state'] = $data['availability_state'] ?? 'unknown';
        $data['health_state'] = $data['health_state'] ?? 'unknown';

        WarningStationDevice::updateOrCreate(['device_code' => $data['device_code']], $data);

        return back()->with('message', 'Warning station device berhasil disimpan.');
    }

    public function heartbeat(Request $request, WarningStation $station): JsonResponse
    {
        $token = env('WARNING_STATION_CALLBACK_TOKEN') ?: env('MQTT_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN');

        if ($token && ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json([
                'ok' => false,
                'message' => 'Token heartbeat warning station tidak valid.',
            ], 403);
        }

        $data = $request->validate([
            'device_code' => ['required', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'availability_state' => ['nullable', 'string', 'max:50'],
            'health_state' => ['nullable', 'string', 'max:50'],
            'observed_at' => ['nullable', 'date'],
            'health_payload' => ['nullable', 'array'],
        ]);

        $heartbeat = $this->warningStations->recordHeartbeat($station, $data);

        return response()->json([
            'ok' => true,
            'heartbeat_id' => $heartbeat->id,
            'warning_station_id' => $station->id,
            'device_code' => $heartbeat->device_code,
            'availability_state' => $heartbeat->availability_state,
            'health_state' => $heartbeat->health_state,
            'received_at' => optional($heartbeat->received_at)->toISOString(),
        ]);
    }

    private function authorizeMutation(Request $request, Project $project, string $action): void
    {
        $user = $request->user();
        $allowed = match ($action) {
            'create' => $this->authorization->canCreateSpatialResource($user, $project),
            'edit' => $this->authorization->canEditSpatialResource($user, $project),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function stationProject(WarningStation $station): Project
    {
        $projectId = $this->warningStations->stationProjectId($station);

        return Project::findOrFail($projectId);
    }
}
