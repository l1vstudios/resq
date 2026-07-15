<?php

namespace App\Http\Controllers;

use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\MstPrefix;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use Illuminate\Http\JsonResponse;
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
            'telemetry_id' => ['nullable', 'exists:telemetry_readings,id'],
            'sensor_id' => ['required', 'exists:sensors,id'],
            'data_logger_id' => ['nullable', 'exists:data_loggers,id'],
            'value' => ['nullable', 'string', 'max:255'],
            'alert_level' => ['required', Rule::in(['Normal', 'Waspada', 'Siaga', 'Awas'])],
            'status' => ['required', Rule::in(['Normal', 'Waspada', 'Siaga', 'Awas', 'Danger'])],
            'received_at' => ['nullable', 'date'],
        ]);
        $telemetryId = $data['telemetry_id'] ?? null;
        unset($data['telemetry_id']);

        $reading = $this->upsertTelemetryReading($data, $telemetryId ? (int) $telemetryId : null);

        Sensor::whereKey($data['sensor_id'])->update([
            'value' => $data['value'],
            'alert_level' => $data['alert_level'],
            'status' => $data['status'],
            'last_seen_at' => $reading->received_at ?? now(),
        ]);

        return back()->with('message', 'Telemetry reading berhasil disimpan.');
    }

    public function updateRealtimeSensorStatus(Request $request): JsonResponse
    {
        $callbackToken = env('MQTT_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN');

        if ($callbackToken && ! hash_equals($callbackToken, (string) $request->bearerToken())) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid callback token.',
            ], 403);
        }

        $data = $request->validate([
            'sensor_id' => ['nullable', 'required_without:sensor_code', 'exists:sensors,id'],
            'sensor_code' => ['nullable', 'required_without:sensor_id', 'string', 'exists:sensors,sensor_code'],
            'data_logger_id' => ['nullable', 'exists:data_loggers,id'],
            'value' => ['nullable', 'string', 'max:255'],
            'threshold_exceeded' => ['nullable', 'boolean'],
        ]);

        $sensor = ! empty($data['sensor_id'])
            ? Sensor::findOrFail($data['sensor_id'])
            : Sensor::where('sensor_code', $data['sensor_code'])->firstOrFail();
        $thresholdExceeded = array_key_exists('threshold_exceeded', $data)
            ? (bool) $data['threshold_exceeded']
            : $this->thresholdExceeded($data['value'] ?? null, $sensor->threshold ?? $sensor->rule);
        $level = $thresholdExceeded ? 'Awas' : 'Normal';

        $sensor->update([
            'value' => $data['value'],
            'alert_level' => $level,
            'status' => $level,
            'last_seen_at' => now(),
        ]);

        $this->upsertTelemetryReading([
            'sensor_id' => $sensor->id,
            'data_logger_id' => $data['data_logger_id'] ?? null,
            'value' => $data['value'],
            'alert_level' => $level,
            'status' => $level,
            'received_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'sensor' => [
                'id' => $sensor->id,
                'sensor_code' => $sensor->sensor_code,
                'value' => $sensor->value,
                'alert_level' => $sensor->alert_level,
                'status' => $sensor->status,
                'last_seen_at' => optional($sensor->last_seen_at)->toISOString(),
            ],
        ]);
    }

    private function thresholdExceeded(?string $value, ?string $threshold): bool
    {
        $numericValue = $this->numericFromText($value);
        $numericThreshold = $this->numericFromText($threshold);

        return $numericValue !== null
            && $numericThreshold !== null
            && $numericValue > $numericThreshold;
    }

    private function upsertTelemetryReading(array $data, ?int $telemetryId = null): TelemetryReading
    {
        $reading = $telemetryId
            ? TelemetryReading::findOrFail($telemetryId)
            : TelemetryReading::where('sensor_id', $data['sensor_id'])
                ->latest('received_at')
                ->latest()
                ->first();

        if ($reading) {
            $reading->update($data);
        } else {
            $reading = TelemetryReading::create($data);
        }

        TelemetryReading::where('sensor_id', $data['sensor_id'])
            ->whereKeyNot($reading->id)
            ->delete();

        return $reading;
    }

    private function numericFromText(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        preg_match('/-?\d+(\.\d+)?/', str_replace(',', '.', $value), $matches);

        return isset($matches[0]) ? (float) $matches[0] : null;
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
