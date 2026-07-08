<?php

namespace App\Http\Controllers;

use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\MstPrefix;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceSetupController extends Controller
{
    public function storeMstPrefix(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prefix_code' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        MstPrefix::updateOrCreate(['prefix_code' => $data['prefix_code']], $data);

        return back()->with('message', 'Prefix sensors berhasil disimpan.');
    }

    public function storeDataLogger(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'monitoring_station_id' => ['nullable', 'exists:monitoring_stations,id'],
            'logger_code' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'logger_model' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'firmware_version' => ['nullable', 'string', 'max:255'],
            'device_label' => ['nullable', 'string', 'max:255'],
            'logger_status' => ['required', 'string', 'max:50'],
        ]);

        DataLogger::updateOrCreate(['logger_code' => $data['logger_code']], $data);

        return back()->with('message', 'Data logger berhasil disimpan.');
    }

    public function storeConnectivity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'data_logger_id' => ['required', 'exists:data_loggers,id'],
            'connectivity_code' => ['required', 'string', 'max:255'],
            'communication_type' => ['nullable', 'string', 'max:255'],
            'protocol' => ['nullable', 'string', 'max:255'],
            'host_or_endpoint' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'topic_or_api_path' => ['nullable', 'string', 'max:255'],
            'gateway_id' => ['nullable', 'string', 'max:255'],
            'sim_number' => ['nullable', 'string', 'max:255'],
            'imei' => ['nullable', 'string', 'max:255'],
            'apn' => ['nullable', 'string', 'max:255'],
            'connectivity_status' => ['required', 'string', 'max:50'],
        ]);

        ConnectivityConfig::updateOrCreate(['connectivity_code' => $data['connectivity_code']], $data);

        return back()->with('message', 'Connectivity berhasil disimpan.');
    }

    public function storeCredential(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'data_logger_id' => ['required', 'exists:data_loggers,id'],
            'credential_code' => ['required', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:255'],
            'mqtt_username' => ['nullable', 'string', 'max:255'],
            'mqtt_password_hash' => ['nullable', 'string', 'max:255'],
            'certificate_ref' => ['nullable', 'string', 'max:255'],
            'credential_status' => ['required', 'string', 'max:50'],
            'revoked_at' => ['nullable', 'date'],
        ]);

        DeviceCredential::updateOrCreate(['credential_code' => $data['credential_code']], $data);

        return back()->with('message', 'Credential berhasil disimpan.');
    }

    public function storeTelemetry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sensor_id' => ['required', 'exists:sensors,id'],
            'data_logger_id' => ['nullable', 'exists:data_loggers,id'],
            'value' => ['nullable', 'string', 'max:255'],
            'alert_level' => ['required', Rule::in(['Normal', 'Waspada', 'Siaga', 'Awas'])],
            'status' => ['required', Rule::in(['Normal', 'Waspada', 'Siaga', 'Awas', 'Danger'])],
            'received_at' => ['nullable', 'date'],
        ]);

        $reading = TelemetryReading::create($data);

        Sensor::whereKey($data['sensor_id'])->update([
            'value' => $data['value'],
            'alert_level' => $data['alert_level'],
            'status' => $data['status'],
            'last_seen_at' => $reading->received_at ?? now(),
        ]);

        return back()->with('message', 'Telemetry reading berhasil disimpan.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $models = [
            'data-logger' => DataLogger::class,
            'connectivity' => ConnectivityConfig::class,
            'credential' => DeviceCredential::class,
            'telemetry' => TelemetryReading::class,
            'mst-prefix' => MstPrefix::class,
        ];

        abort_unless(isset($models[$type]), 404);
        $models[$type]::findOrFail($id)->delete();

        return back()->with('message', 'Data berhasil dihapus.');
    }
}
