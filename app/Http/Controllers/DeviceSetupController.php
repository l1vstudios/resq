<?php

namespace App\Http\Controllers;

use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Services\CanonicalMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use phpseclib3\Net\SSH2;

class DeviceSetupController extends Controller
{
    public function __construct(private readonly CanonicalMappingService $canonicalMapping)
    {
    }

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
            'remote_host' => ['nullable', 'string', 'max:255'],
            'remote_ssh_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remote_ssh_user' => ['nullable', 'string', 'max:255'],
            'remote_ssh_password' => ['nullable', 'string', 'max:255'],
            'remote_gateway_path' => ['nullable', 'string', 'max:255'],
            'logger_status' => ['required', 'string', 'max:50'],
        ]);

        $logger = DataLogger::where('logger_code', $data['logger_code'])->first();

        if ($logger && empty($data['remote_ssh_password'])) {
            unset($data['remote_ssh_password']);
        }

        DataLogger::updateOrCreate(['logger_code' => $data['logger_code']], $data);

        return back()->with('message', 'Data logger berhasil disimpan.');
    }

    public function testDataLoggerRemote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'data_logger_id' => ['required', 'exists:data_loggers,id'],
        ]);
        $logger = DataLogger::findOrFail($data['data_logger_id']);
        $host = trim((string) $logger->remote_host);

        if ($host === '') {
            return response()->json([
                'ok' => false,
                'message' => 'IP / host remote logger belum diisi.',
            ], 422);
        }

        $timeoutArg = PHP_OS_FAMILY === 'Darwin' ? '2000' : '2';
        $command = 'ping -c 1 -W ' . $timeoutArg . ' ' . escapeshellarg($host);
        $output = [];
        $exitCode = 1;

        exec($command . ' 2>&1', $output, $exitCode);

        $ok = $exitCode === 0;
        $message = $ok
            ? 'Logger bisa dijangkau dari server.'
            : 'Logger belum bisa dijangkau. Cek IP, jaringan, atau firewall.';

        $logger->update([
            'remote_last_tested_at' => now(),
            'remote_last_status' => $ok ? 'Success' : 'Failed',
            'remote_last_message' => $message,
        ]);

        return response()->json([
            'ok' => $ok,
            'message' => $message,
            'host' => $host,
            'tested_at' => $logger->remote_last_tested_at?->toISOString(),
            'output' => trim(implode("\n", array_slice($output, 0, 6))),
        ], $ok ? 200 : 422);
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
            'serial_port' => ['nullable', 'string', 'max:255'],
            'baud_rate' => ['nullable', 'integer', 'min:300', 'max:1000000'],
            'data_bits' => ['nullable', 'integer', 'min:5', 'max:8'],
            'stop_bits' => ['nullable', 'integer', 'min:1', 'max:2'],
            'parity' => ['nullable', Rule::in(['none', 'even', 'odd'])],
            'timeout_ms' => ['nullable', 'integer', 'min:100', 'max:60000'],
            'pin_mapping' => ['nullable', 'string', 'max:255'],
            'sim_number' => ['nullable', 'string', 'max:255'],
            'imei' => ['nullable', 'string', 'max:255'],
            'apn' => ['nullable', 'string', 'max:255'],
            'connectivity_status' => ['required', 'string', 'max:50'],
        ]);

        ConnectivityConfig::updateOrCreate(['connectivity_code' => $data['connectivity_code']], $data);

        return back()->with('message', 'Connectivity berhasil disimpan.');
    }

    public function storeRednodeSerialConfig(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge([
            'rednode_ssh_port' => $request->input('rednode_ssh_port') === '' ? null : $request->input('rednode_ssh_port'),
        ]);

        $data = $request->validate([
            'data_logger_id' => ['nullable', 'exists:data_loggers,id'],
            'logger_code' => ['required', 'string', 'max:255'],
            'serial_port' => ['required', 'string', 'max:255'],
            'baud_rate' => ['required', 'integer', 'min:300', 'max:1000000'],
            'data_bits' => ['required', 'integer', 'min:5', 'max:8'],
            'stop_bits' => ['required', 'integer', 'min:1', 'max:2'],
            'parity' => ['required', Rule::in(['none', 'even', 'odd'])],
            'timeout_ms' => ['required', 'integer', 'min:100', 'max:60000'],
            'pin_mapping' => ['nullable', 'string', 'max:255'],
            'monitored_sensor_ids' => ['nullable', 'array'],
            'monitored_sensor_ids.*' => ['integer', 'exists:sensors,id'],
            'rednode_host' => ['nullable', 'string', 'max:255'],
            'rednode_ssh_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'rednode_ssh_user' => ['nullable', 'string', 'max:255'],
            'rednode_ssh_password' => ['nullable', 'string', 'max:255'],
            'rednode_gateway_path' => ['nullable', 'string', 'max:255'],
            'rednode_poll_interval_seconds' => ['required', 'numeric', 'min:0.25', 'max:3600'],
        ]);
        $monitoredSensorIds = collect($data['monitored_sensor_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($request->has('monitored_sensor_ids_present') && empty($monitoredSensorIds)) {
            throw ValidationException::withMessages([
                'monitored_sensor_ids' => 'Pilih minimal satu sensor untuk dimonitor RedNode.',
            ]);
        }

        $logger = ! empty($data['data_logger_id'])
            ? DataLogger::findOrFail($data['data_logger_id'])
            : DataLogger::updateOrCreate(
                ['logger_code' => $data['logger_code']],
                [
                    'logger_model' => 'RedNode Bliiot',
                    'vendor' => 'Bliiot',
                    'device_label' => $data['logger_code'],
                    'logger_status' => 'Active',
                ]
            );

        if ($logger->logger_code !== $data['logger_code']) {
            $logger->update(['logger_code' => $data['logger_code']]);
        }

        $connectivityCode = 'SERIAL-' . $logger->logger_code;
        $existingConnectivity = ConnectivityConfig::where('connectivity_code', $connectivityCode)->first();
        $connectivityValues = [
                'data_logger_id' => $logger->id,
                'communication_type' => 'RS485',
                'protocol' => 'Modbus RTU',
                'host_or_endpoint' => $data['serial_port'],
                'port' => null,
                'topic_or_api_path' => $data['pin_mapping'],
                'gateway_id' => $logger->logger_code,
                'serial_port' => $data['serial_port'],
                'baud_rate' => $data['baud_rate'],
                'data_bits' => $data['data_bits'],
                'stop_bits' => $data['stop_bits'],
                'parity' => $data['parity'],
                'timeout_ms' => $data['timeout_ms'],
                'pin_mapping' => $data['pin_mapping'] ?? null,
                'monitored_sensor_ids' => $request->has('monitored_sensor_ids_present') ? $monitoredSensorIds : null,
                'rednode_host' => ($data['rednode_host'] ?? null) ?: $logger->remote_host ?: env('REDNODE_SSH_HOST'),
                'rednode_ssh_port' => ($data['rednode_ssh_port'] ?? null) ?: $logger->remote_ssh_port ?: env('REDNODE_SSH_PORT', 22),
                'rednode_ssh_user' => ($data['rednode_ssh_user'] ?? null) ?: $logger->remote_ssh_user ?: env('REDNODE_SSH_USER', 'root'),
                'rednode_gateway_path' => ($data['rednode_gateway_path'] ?? null) ?: $logger->remote_gateway_path ?: env('REDNODE_GATEWAY_PATH', '/root/rednode-gateway'),
                'rednode_poll_interval_ms' => (int) round(((float) $data['rednode_poll_interval_seconds']) * 1000),
                'connectivity_status' => $existingConnectivity?->connectivity_status ?? 'Offline',
        ];

        if (! empty($data['rednode_ssh_password'] ?? null) || $logger->remote_ssh_password) {
            $connectivityValues['rednode_ssh_password'] = ($data['rednode_ssh_password'] ?? null) ?: $logger->remote_ssh_password;
        }

        $connectivity = ConnectivityConfig::updateOrCreate(
            ['connectivity_code' => $connectivityCode],
            $connectivityValues
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Konfigurasi serial RedNode berhasil disimpan.',
                'rednode' => [
                    'logger_code' => $logger->logger_code,
                    'serial_port' => $connectivity->serial_port,
                    'pin_mapping' => $connectivity->pin_mapping,
                    'baud_rate' => $connectivity->baud_rate,
                    'data_bits' => $connectivity->data_bits,
                    'stop_bits' => $connectivity->stop_bits,
                    'parity' => $connectivity->parity,
                    'timeout_ms' => $connectivity->timeout_ms,
                    'monitored_sensor_ids' => $connectivity->monitored_sensor_ids ?? [],
                    'rednode_host' => $connectivity->rednode_host,
                    'rednode_gateway_path' => $connectivity->rednode_gateway_path,
                    'rednode_poll_interval_ms' => $connectivity->rednode_poll_interval_ms,
                ],
            ]);
        }

        return back()->with('message', 'Konfigurasi serial RedNode berhasil disimpan.');
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
            'parameter_values' => ['nullable', 'array'],
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
            'data_logger_code' => ['nullable', 'string', 'exists:data_loggers,logger_code'],
            'value' => ['nullable', 'string', 'max:255'],
            'display_value' => ['nullable', 'string', 'max:255'],
            'raw_value' => ['nullable', 'string', 'max:255'],
            'numeric_value' => ['nullable', 'numeric'],
            'parameter_values' => ['nullable', 'array'],
            'threshold_exceeded' => ['nullable', 'boolean'],
            'observed_at' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
        ]);

        $sensor = ! empty($data['sensor_id'])
            ? Sensor::findOrFail($data['sensor_id'])
            : Sensor::where('sensor_code', $data['sensor_code'])->firstOrFail();
        $dataLoggerId = $data['data_logger_id'] ?? $sensor->data_logger_id;
        if (! $dataLoggerId && ! empty($data['data_logger_code'])) {
            $dataLoggerId = DataLogger::where('logger_code', $data['data_logger_code'])->value('id');
        }

        $mappingValue = array_key_exists('raw_value', $data) ? $data['raw_value'] : ($data['value'] ?? null);
        $displayValue = $data['display_value'] ?? $data['value'] ?? null;
        $readingValue = $sensor->type !== 'weather_station'
            && $this->canonicalMapping->activeProfileForSensor($sensor)
            && array_key_exists('raw_value', $data)
            ? $data['raw_value']
            : $displayValue;
        $thresholdExceeded = array_key_exists('threshold_exceeded', $data)
            ? (bool) $data['threshold_exceeded']
            : $this->thresholdExceeded($data['numeric_value'] ?? $displayValue, $sensor->threshold ?? $sensor->rule);
        $level = $thresholdExceeded ? 'Awas' : 'Normal';

        $sensor->update([
            'value' => $displayValue,
            'alert_level' => $level,
            'status' => $level,
            'last_seen_at' => now(),
        ]);

        $telemetryPayload = [
            'sensor_id' => $sensor->id,
            'data_logger_id' => $dataLoggerId,
            'value' => $readingValue,
            'alert_level' => $level,
            'status' => $level,
            'received_at' => now(),
        ];

        if (Schema::hasColumn('telemetry_readings', 'parameter_values')) {
            $telemetryPayload['parameter_values'] = $data['parameter_values'] ?? null;
        }

        $this->upsertTelemetryReading($telemetryPayload);

        $canonicalObservation = $this->canonicalMapping->storeObservation(
            $sensor,
            $mappingValue,
            $dataLoggerId,
            ! empty($data['observed_at']) ? Carbon::parse($data['observed_at']) : now(),
            $data['payload'] ?? $request->all()
        );
        $mappedValue = $this->canonicalMapping->mappedParameterValue($sensor, $mappingValue);

        return response()->json([
            'ok' => true,
            'sensor' => [
                'id' => $sensor->id,
                'sensor_code' => $sensor->sensor_code,
                'value' => $sensor->value,
                'raw_value' => $mappingValue,
                'alert_level' => $sensor->alert_level,
                'status' => $sensor->status,
                'last_seen_at' => optional($sensor->last_seen_at)->toISOString(),
                'canonical_observation_id' => $canonicalObservation?->id,
                'canonical_parameter_value' => $mappedValue,
                'parameter_values' => $data['parameter_values'] ?? ($mappedValue ? [$mappedValue] : []),
            ],
        ]);
    }

    public function rednodeConfig(Request $request): JsonResponse
    {
        $configToken = env('REDNODE_CONFIG_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN') ?: env('MQTT_CALLBACK_TOKEN');

        if ($configToken && ! hash_equals($configToken, (string) $request->bearerToken())) {
            return response()->json([
                'ok' => false,
                'message' => 'Token config RedNode tidak valid.',
            ], 403);
        }

        $requestedLoggerCode = $request->query('logger_code', env('REDNODE_LOGGER_CODE', 'REDNODE-BLIIOT-01'));
        $dataLogger = Schema::hasTable('data_loggers')
            ? $this->resolveRednodeLogger($requestedLoggerCode)
            : null;
        $loggerCode = $dataLogger?->logger_code ?? $requestedLoggerCode;
        $serialConfig = $dataLogger && Schema::hasTable('connectivity_configs')
            ? ConnectivityConfig::where('data_logger_id', $dataLogger->id)
                ->where(function ($query) {
                    $query->where('protocol', 'Modbus RTU')
                        ->orWhereNotNull('serial_port');
                })
                ->latest()
                ->first()
            : null;
        $serialSettings = $serialConfig?->serial_settings ?? [];
        $selectedSensorIds = collect($serialConfig?->monitored_sensor_ids ?? ($serialSettings['monitored_sensor_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $rednodePollIntervalMs = (int) ($serialConfig?->rednode_poll_interval_ms ?: ($serialSettings['rednode_poll_interval_ms'] ?? env('REDNODE_POLL_INTERVAL_MS', 1000)));

        $sensorQuery = Sensor::with(['monitoringStation', 'mstPrefix'])
            ->whereNotNull('slave_id')
            ->whereNotNull('address')
            ->orderBy('monitoring_station_id')
            ->orderBy('slave_id')
            ->orderBy('address');

        if ($dataLogger?->monitoring_station_id) {
            $sensorQuery->where('monitoring_station_id', $dataLogger->monitoring_station_id);
        }

        if ($selectedSensorIds->isNotEmpty()) {
            $sensorQuery->whereIn('id', $selectedSensorIds->all());
        }

        $sensors = $sensorQuery->get()
            ->map(function (Sensor $sensor) use ($rednodePollIntervalMs) {
                return array_merge($this->canonicalMapping->rednodeSensorConfig($sensor), [
                    'sensor_label' => $this->sensorLabel($sensor),
                    'weather_parameter_labels' => collect($sensor->weather_parameters ?? [])
                        ->map(fn ($parameter) => $this->weatherParameterLabel((string) $parameter))
                        ->values(),
                    'monitoring_station_id' => $sensor->monitoringStation?->station_code,
                    'prefix' => $sensor->mstPrefix?->prefix_code,
                    'poll_interval_ms' => $rednodePollIntervalMs > 0
                        ? $rednodePollIntervalMs
                        : (int) ($sensor->poll_interval_ms ?: 1000),
                    'status' => $sensor->status,
                ]);
            })
            ->values();

        $publicAppUrl = $this->rednodePublicAppUrl();

        return response()->json([
            'ok' => true,
            'generated_at' => now()->toISOString(),
            'logger_code' => $loggerCode,
            'requested_logger_code' => $requestedLoggerCode,
            'data_logger_id' => $dataLogger?->id,
            'message' => $sensors->isEmpty()
                ? 'Tidak ada sensor untuk logger ini. Pastikan Sensor & Data sudah memilih Data Logger atau station logger yang sama.'
                : null,
            'logger' => [
                'id' => $dataLogger?->id,
                'logger_code' => $dataLogger?->logger_code ?: $loggerCode,
                'requested_logger_code' => $requestedLoggerCode,
                'device_label' => $dataLogger?->device_label ?: 'RedNode Bliiot',
            ],
            'serial' => [
                'port' => $serialConfig?->serial_port ?: ($serialSettings['serial_port'] ?? ($serialConfig?->host_or_endpoint ?: env('REDNODE_SERIAL_PORT', '/dev/ttyAS2'))),
                'baud_rate' => (int) ($serialConfig?->baud_rate ?: ($serialSettings['baud_rate'] ?? env('REDNODE_BAUD_RATE', 9600))),
                'data_bits' => (int) ($serialConfig?->data_bits ?: ($serialSettings['data_bits'] ?? env('REDNODE_DATA_BITS', 8))),
                'stop_bits' => (int) ($serialConfig?->stop_bits ?: ($serialSettings['stop_bits'] ?? env('REDNODE_STOP_BITS', 1))),
                'parity' => $serialConfig?->parity ?: ($serialSettings['parity'] ?? env('REDNODE_PARITY', 'none')),
                'timeout_ms' => (int) ($serialConfig?->timeout_ms ?: ($serialSettings['timeout_ms'] ?? env('REDNODE_TIMEOUT_MS', 1500))),
                'pin_mapping' => $serialConfig?->pin_mapping ?: ($serialSettings['pin_mapping'] ?? $serialConfig?->topic_or_api_path),
                'monitored_sensor_ids' => $selectedSensorIds,
                'poll_interval_ms' => $rednodePollIntervalMs,
            ],
            'monitoring' => [
                'enabled' => $serialConfig?->connectivity_status !== 'Offline',
                'last_action' => $serialConfig?->connectivity_status === 'Offline' ? 'stop' : 'start',
                'last_seen_at' => optional($serialConfig?->last_seen_at)->toISOString(),
                'last_error' => $serialConfig?->last_error,
            ],
            'callback' => [
                'url' => $this->rednodeSetting('REDNODE_CALLBACK_URL')
                    ?: $this->rednodeSetting('MQTT_CALLBACK_URL')
                    ?: $publicAppUrl . '/api/realtime-sensor-status',
                'token_required' => (bool) (env('MQTT_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN')),
            ],
            'mqtt' => [
                'enabled' => filter_var(env('REDNODE_MQTT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
                'broker_url' => env('REDNODE_MQTT_BROKER_URL') ?: env('MQTT_BROKER_URL'),
                'topic_prefix' => env('REDNODE_MQTT_TOPIC_PREFIX', 'resq/telemetry'),
                'username' => env('REDNODE_MQTT_USERNAME') ?: env('MQTT_USERNAME'),
            ],
            'heartbeat' => [
                'url' => $this->rednodeSetting('REDNODE_HEARTBEAT_URL')
                    ?: $publicAppUrl . '/api/rednode/heartbeat',
            ],
            'connection_report' => [
                'url' => $this->rednodeSetting('REDNODE_HEARTBEAT_URL')
                    ?: $publicAppUrl . '/api/rednode/heartbeat',
            ],
            'sensors' => $sensors,
        ]);
    }

    private function resolveRednodeLogger(string $loggerCode): ?DataLogger
    {
        $logger = DataLogger::where('logger_code', $loggerCode)->first();

        if ($logger) {
            return $logger;
        }

        $baseCode = preg_replace('/-\d+$/', '', $loggerCode) ?: $loggerCode;
        if ($baseCode !== $loggerCode) {
            $logger = DataLogger::where('logger_code', $baseCode)->first();
            if ($logger) {
                return $logger;
            }
        }

        return DataLogger::where('logger_code', 'like', $baseCode . '%')
            ->orWhere('logger_code', 'like', $loggerCode . '%')
            ->orderBy('logger_code')
            ->first();
    }

    public function rednodeHeartbeat(Request $request): JsonResponse
    {
        $callbackToken = env('REDNODE_CALLBACK_TOKEN') ?: env('MQTT_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN');

        if ($callbackToken && ! hash_equals($callbackToken, (string) $request->bearerToken())) {
            return response()->json([
                'ok' => false,
                'message' => 'Token laporan koneksi RedNode tidak valid.',
            ], 403);
        }

        $data = $request->validate([
            'logger_code' => ['required', 'string', 'max:255'],
            'serial_port' => ['nullable', 'string', 'max:255'],
            'pin_mapping' => ['nullable', 'string', 'max:255'],
            'connected' => ['required', 'boolean'],
            'last_error' => ['nullable', 'string'],
            'sensors' => ['nullable', 'array'],
        ]);

        $logger = DataLogger::firstOrCreate(
            ['logger_code' => $data['logger_code']],
            [
                'logger_model' => 'RedNode Bliiot',
                'vendor' => 'Bliiot',
                'device_label' => $data['logger_code'],
                'logger_status' => 'Active',
            ]
        );

        $connectivity = ConnectivityConfig::firstOrCreate(
            ['connectivity_code' => 'SERIAL-' . $logger->logger_code],
            [
                'data_logger_id' => $logger->id,
                'communication_type' => 'RS485',
                'protocol' => 'Modbus RTU',
                'gateway_id' => $logger->logger_code,
            ]
        );

        $connectivity->update([
            'data_logger_id' => $logger->id,
            'host_or_endpoint' => $data['serial_port'] ?? $connectivity->host_or_endpoint,
            'serial_port' => $data['serial_port'] ?? $connectivity->serial_port,
            'pin_mapping' => $data['pin_mapping'] ?? $connectivity->pin_mapping,
            'connectivity_status' => $data['connected'] ? 'Online' : 'Offline',
            'last_seen_at' => now(),
            'last_error' => $data['last_error'] ?? null,
            'last_payload' => [
                'sensors' => $data['sensors'] ?? [],
                'reported_at' => now()->toISOString(),
            ],
        ]);
        $this->syncRednodeHeartbeatSensors($data['sensors'] ?? [], $logger->id);

        return response()->json([
            'ok' => true,
            'status' => $connectivity->connectivity_status,
            'last_seen_at' => $connectivity->last_seen_at?->toISOString(),
        ]);
    }

    public function rednodeStatus(Request $request): JsonResponse
    {
        $loggerCode = $request->query('logger_code', env('REDNODE_LOGGER_CODE', 'REDNODE-BLIIOT-01'));
        $logger = DataLogger::where('logger_code', $loggerCode)->first();
        $connectivity = $logger
            ? ConnectivityConfig::where('data_logger_id', $logger->id)
                ->where(function ($query) {
                    $query->where('protocol', 'Modbus RTU')
                        ->orWhereNotNull('serial_port');
                })
                ->latest()
                ->first()
            : null;
        $selectedSensorIds = collect($connectivity?->monitored_sensor_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $latestReadingsQuery = TelemetryReading::with('sensor')
            ->latest('received_at')
            ->latest()
            ->limit(10);

        if ($selectedSensorIds->isNotEmpty()) {
            $latestReadingsQuery->whereIn('sensor_id', $selectedSensorIds->all());
        }

        $freshReadingAfter = now()->subSeconds((int) env('PROJECT_MONITORING_FRESH_SECONDS', 90));
        $latestReadings = $latestReadingsQuery->get()
            ->map(function (TelemetryReading $reading) use ($freshReadingAfter) {
                $receivedAt = $reading->received_at ?? $reading->created_at;
                $fresh = $receivedAt && $receivedAt->gt($freshReadingAfter);

                return [
                    'sensor_code' => $reading->sensor?->sensor_code,
                    'sensor_label' => $reading->sensor ? $this->sensorLabel($reading->sensor) : $reading->sensor?->sensor_code,
                    'sensor_type' => $reading->sensor?->type,
                    'parameter' => $reading->sensor?->parameter,
                    'weather_parameters' => $reading->sensor?->weather_parameters ?? [],
                    'parameter_values' => $reading->parameter_values ?? [],
                    'value' => $reading->value,
                    'status' => $fresh ? $reading->status : 'Data Lama',
                    'alert_level' => $reading->alert_level,
                    'fresh' => (bool) $fresh,
                    'received_at' => optional($receivedAt)->toISOString(),
                ];
            });
        $lastSeen = $connectivity?->last_seen_at;
        $online = $lastSeen && $lastSeen->gt(now()->subSeconds(45)) && $connectivity->connectivity_status === 'Online';

        return response()->json([
            'ok' => true,
            'online' => (bool) $online,
            'logger_code' => $loggerCode,
            'serial_port' => $connectivity?->serial_port ?: $connectivity?->host_or_endpoint,
            'pin_mapping' => $connectivity?->pin_mapping,
            'connectivity_status' => $connectivity?->connectivity_status ?? 'Offline',
            'last_seen_at' => optional($lastSeen)->toISOString(),
            'last_error' => $connectivity?->last_error,
            'last_payload' => $connectivity?->last_payload,
            'monitored_sensor_ids' => $selectedSensorIds,
            'rednode_host' => $connectivity?->rednode_host,
            'rednode_gateway_path' => $connectivity?->rednode_gateway_path,
            'rednode_poll_interval_ms' => $connectivity?->rednode_poll_interval_ms,
            'latest_readings' => $latestReadings,
        ]);
    }

    public function rednodeControl(Request $request): JsonResponse
    {
        $data = $request->validate([
            'logger_code' => ['required', 'string', 'max:255'],
            'action' => ['required', Rule::in(['start', 'stop'])],
        ]);

        $connectivity = $this->rednodeConnectivity($data['logger_code']);

        if (! $connectivity) {
            return response()->json([
                'ok' => false,
                'message' => 'Konfigurasi RedNode belum ditemukan. Apply konfigurasi dulu.',
            ], 422);
        }

        $command = $data['action'] === 'start'
            ? $this->rednodeStopCommand($connectivity) . '; ' . $this->rednodeStartCommand($connectivity, $data['logger_code'], $this->rednodeRequestBaseUrl($request))
            : $this->rednodeStopCommand($connectivity);
        $terminalLog = [
            '$ ssh ' . $this->rednodeSshLabel($connectivity),
            '[web] Remote SSH ke logger...',
        ];
        $result = trim($this->runRednodeSshCommand(
            $connectivity,
            $command,
            $data['action'] === 'start' ? 70 : 35
        ));
        $terminalLog = array_merge($terminalLog, $this->rednodeTerminalOutputLines($result));
        $connectivity->update([
            'connectivity_status' => $data['action'] === 'start' ? 'Online' : 'Offline',
            'last_seen_at' => now(),
            'last_error' => $data['action'] === 'start' ? null : 'Gateway RedNode dihentikan dari web.',
        ]);
        $message = $data['action'] === 'start'
            ? 'Remote SSH berhasil restart bersih RedNode di logger.'
            : 'Remote SSH berhasil menghentikan RedNode di logger.';

        return response()->json([
            'ok' => true,
            'message' => $message,
            'online' => $data['action'] === 'start',
            'last_seen_at' => $connectivity->last_seen_at?->toISOString(),
            'output' => $result,
            'terminal_log' => $terminalLog,
        ]);
    }

    public function startProjectMonitoring(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
        ]);
        $project = Project::findOrFail($data['project_id']);
        $loggers = $this->projectDataLoggers((int) $project->id);

        if ($loggers->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Project ini belum punya data logger.',
            ], 422);
        }

        $results = $loggers->map(function (DataLogger $logger) use ($request) {
            $connectivity = $this->rednodeConnectivity($logger->logger_code);

            if (! $connectivity) {
                return [
                    'logger_code' => $logger->logger_code,
                    'station' => $logger->monitoringStation?->station_code,
                    'ok' => false,
                    'message' => 'Konfigurasi serial RedNode belum ditemukan.',
                    'terminal_log' => [
                        '[web] Konfigurasi serial RedNode belum ditemukan untuk logger ini.',
                    ],
                ];
            }

            try {
                $output = trim($this->runRednodeSshCommand(
                    $connectivity,
                    $this->rednodeStopCommand($connectivity) . '; ' . $this->rednodeStartCommand($connectivity, $logger->logger_code, $this->rednodeRequestBaseUrl($request)),
                    70
                ));
                $terminalLog = array_merge(
                    [
                        '[web] Mulai monitoring logger ' . $logger->logger_code,
                        '$ ssh ' . $this->rednodeSshLabel($connectivity),
                    ],
                    $this->rednodeTerminalOutputLines($output)
                );
                $connectivity->update([
                    'connectivity_status' => 'Online',
                    'last_seen_at' => now(),
                    'last_error' => null,
                ]);

                return [
                    'logger_code' => $logger->logger_code,
                    'station' => $logger->monitoringStation?->station_code,
                    'ok' => true,
                    'message' => 'Remote SSH berhasil. Gateway RedNode restart bersih dan mulai jalan.',
                    'terminal_log' => $terminalLog,
                ];
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())->flatten()->first() ?: 'Start gateway gagal.';
                $connectivity->update([
                    'connectivity_status' => 'Offline',
                    'last_error' => $message,
                ]);

                return [
                    'logger_code' => $logger->logger_code,
                    'station' => $logger->monitoringStation?->station_code,
                    'ok' => false,
                    'message' => $message,
                    'terminal_log' => array_merge(
                        [
                            '[web] Mulai monitoring logger ' . $logger->logger_code,
                            '$ ssh ' . $this->rednodeSshLabel($connectivity),
                        ],
                        $this->rednodeTerminalOutputLines($message)
                    ),
                ];
            }
        })->values();

        $successCount = $results->where('ok', true)->count();

        return response()->json([
            'ok' => $successCount > 0,
            'message' => $successCount
                ? 'Monitoring project dimulai untuk ' . $successCount . ' logger.'
                : 'Monitoring project gagal dimulai. Cek IP dan credentials logger.',
            'project' => [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'name' => $project->name,
            ],
            'loggers' => $results,
        ], $successCount > 0 ? 200 : 422);
    }

    public function stopProjectMonitoring(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
        ]);
        $project = Project::findOrFail($data['project_id']);
        $loggers = $this->projectDataLoggers((int) $project->id);

        if ($loggers->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Project ini belum punya data logger.',
            ], 422);
        }

        $results = $loggers->map(function (DataLogger $logger) {
            $connectivity = $this->rednodeConnectivity($logger->logger_code);

            if (! $connectivity) {
                return [
                    'logger_code' => $logger->logger_code,
                    'station' => $logger->monitoringStation?->station_code,
                    'ok' => false,
                    'message' => 'Konfigurasi serial RedNode belum ditemukan.',
                    'terminal_log' => [
                        '[web] Konfigurasi serial RedNode belum ditemukan untuk logger ini.',
                    ],
                ];
            }

            try {
                $output = trim($this->runRednodeSshCommand(
                    $connectivity,
                    $this->rednodeStopCommand($connectivity),
                    45
                ));
                $terminalLog = array_merge(
                    [
                        '[web] Stop monitoring logger ' . $logger->logger_code,
                        '$ ssh ' . $this->rednodeSshLabel($connectivity),
                    ],
                    $this->rednodeTerminalOutputLines($output)
                );
                $connectivity->update([
                    'connectivity_status' => 'Offline',
                    'last_seen_at' => now(),
                    'last_error' => 'Monitoring dihentikan dari web.',
                ]);

                return [
                    'logger_code' => $logger->logger_code,
                    'station' => $logger->monitoringStation?->station_code,
                    'ok' => true,
                    'message' => 'Remote SSH berhasil menghentikan gateway RedNode.',
                    'terminal_log' => $terminalLog,
                ];
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())->flatten()->first() ?: 'Stop gateway gagal.';
                $connectivity->update([
                    'connectivity_status' => 'Offline',
                    'last_error' => $message,
                ]);

                return [
                    'logger_code' => $logger->logger_code,
                    'station' => $logger->monitoringStation?->station_code,
                    'ok' => false,
                    'message' => $message,
                    'terminal_log' => array_merge(
                        [
                            '[web] Stop monitoring logger ' . $logger->logger_code,
                            '$ ssh ' . $this->rednodeSshLabel($connectivity),
                        ],
                        $this->rednodeTerminalOutputLines($message)
                    ),
                ];
            }
        })->values();

        $successCount = $results->where('ok', true)->count();

        return response()->json([
            'ok' => $successCount > 0,
            'message' => $successCount
                ? 'Monitoring project dihentikan untuk ' . $successCount . ' logger.'
                : 'Monitoring project gagal dihentikan. Cek IP dan credentials logger.',
            'project' => [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'name' => $project->name,
            ],
            'loggers' => $results,
        ], $successCount > 0 ? 200 : 422);
    }

    public function projectMonitoringLiveData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
        ]);
        $project = Project::findOrFail($data['project_id']);
        $freshAfter = now()->subSeconds((int) env('PROJECT_MONITORING_FRESH_SECONDS', 90));
        $loggers = $this->projectDataLoggers((int) $project->id);
        $loggerIds = $loggers->pluck('id')->filter()->values();
        $sensors = Sensor::with(['monitoringStation', 'workspace'])
            ->whereHas('monitoringStation.workspace', fn ($query) => $query->where('project_id', $project->id))
            ->orderBy('monitoring_station_id')
            ->orderBy('sensor_code')
            ->get();
        $readings = TelemetryReading::with('dataLogger')
            ->whereIn('sensor_id', $sensors->pluck('id'))
            ->latest('received_at')
            ->latest()
            ->get()
            ->unique('sensor_id')
            ->keyBy('sensor_id');

        $loggerRows = $loggers->map(function (DataLogger $logger) use ($freshAfter) {
            $connectivity = $this->rednodeConnectivity($logger->logger_code);
            $lastSeen = $connectivity?->last_seen_at;
            $online = $lastSeen && $lastSeen->gt($freshAfter) && $connectivity?->connectivity_status === 'Online';

            return [
                'id' => $logger->id,
                'logger_code' => $logger->logger_code,
                'device_label' => $logger->device_label,
                'station' => $logger->monitoringStation?->station_code,
                'online' => (bool) $online,
                'status' => $online ? 'Online' : ($connectivity?->connectivity_status ?? 'Offline'),
                'last_seen_at' => optional($lastSeen)->toISOString(),
                'last_error' => $connectivity?->last_error,
            ];
        })->values();
        $loggerRowsByStation = $loggerRows->keyBy('id');

        $sensorRows = $sensors->map(function (Sensor $sensor) use ($readings, $freshAfter, $loggers, $loggerRowsByStation) {
            $reading = $readings->get($sensor->id);
            $receivedAt = $reading?->received_at ?: $sensor->last_seen_at;
            $logger = $loggers->firstWhere('monitoring_station_id', $sensor->monitoring_station_id);
            $loggerOnline = $logger ? (bool) ($loggerRowsByStation->get($logger->id)['online'] ?? false) : false;
            $readingFresh = $receivedAt && $receivedAt->gt($freshAfter);
            $fresh = $readingFresh && $loggerOnline;
            $parameterValues = collect($reading?->parameter_values ?? [])
                ->map(fn ($item) => is_array($item) ? [
                    'parameter' => $item['parameter'] ?? null,
                    'label' => $item['label'] ?? $this->weatherParameterLabel((string) ($item['parameter'] ?? '')),
                    'value' => $item['value'] ?? null,
                    'value_text' => $this->valueWithUnit($item['value_text'] ?? ($item['value'] ?? null), $item['unit'] ?? null),
                    'unit' => $item['unit'] ?? null,
                    'raw' => $item['raw'] ?? null,
                ] : null)
                ->filter()
                ->values();

            return [
                'id' => $sensor->id,
                'sensor_code' => $sensor->sensor_code,
                'sensor_label' => $this->sensorLabel($sensor),
                'sensor_type' => $sensor->type,
                'parameter' => $sensor->parameter,
                'weather_parameters' => $sensor->weather_parameters ?? [],
                'parameter_values' => $parameterValues,
                'station' => $sensor->monitoringStation?->station_code,
                'logger_code' => $reading?->dataLogger?->logger_code ?: $logger?->logger_code,
                'value' => $sensor->type === 'weather_station' && $parameterValues->isNotEmpty()
                    ? $parameterValues->pluck('value_text')->filter()->implode(', ')
                    : $this->valueWithUnit($reading?->value ?? $sensor->value, $sensor->unit),
                'status' => $fresh ? ($reading?->status ?? $sensor->status ?? 'Normal') : ($loggerOnline ? 'Online - Tunggu Data' : 'Data Lama'),
                'alert_level' => $reading?->alert_level ?? $sensor->alert_level,
                'fresh' => (bool) $fresh,
                'online' => $loggerOnline,
                'received_at' => optional($receivedAt)->toISOString(),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'generated_at' => now()->toISOString(),
            'fresh_after' => $freshAfter->toISOString(),
            'project' => [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'name' => $project->name,
            ],
            'summary' => [
                'loggers' => $loggerRows->count(),
                'online_loggers' => $loggerRows->where('online', true)->count(),
                'sensors' => $sensorRows->count(),
                'fresh_sensors' => $sensorRows->where('fresh', true)->count(),
            ],
            'loggers' => $loggerRows,
            'sensors' => $sensorRows,
        ]);
    }

    public function rednodePortTest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'logger_code' => ['required', 'string', 'max:255'],
        ]);

        $connectivity = $this->rednodeConnectivity($data['logger_code']);

        if (! $connectivity) {
            return response()->json([
                'ok' => false,
                'message' => 'Konfigurasi RedNode belum ditemukan. Apply konfigurasi dulu.',
            ], 422);
        }

        $logger = $connectivity->relationLoaded('dataLogger')
            ? $connectivity->dataLogger
            : $connectivity->dataLogger()->first();
        $gatewayPath = $connectivity->rednode_gateway_path ?: $logger?->remote_gateway_path ?: env('REDNODE_GATEWAY_PATH', '/root/rednode-gateway');
        $script = implode("\n", [
            'exec 2>&1',
            'echo "[preflight] user=$(whoami) host=$(hostname) pwd=$(pwd)"',
            'echo "[preflight] gateway_dir=$GATEWAY_DIR"',
            'cd "$GATEWAY_DIR" || { echo "[error] Gateway path tidak ditemukan: $GATEWAY_DIR"; exit 1; }',
            'export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:$PATH"',
            'NODE_BIN="$(command -v node || true)"',
            'if [ -z "$NODE_BIN" ]; then echo "[error] node binary tidak ditemukan di PATH"; exit 1; fi',
            'echo "[preflight] node=$NODE_BIN"',
            'if [ ! -f test-ports.js ]; then echo "[error] test-ports.js tidak ditemukan di $(pwd)"; ls -la; exit 1; fi',
            'env ' . $this->rednodeRuntimeEnvString($data['logger_code']) . ' "$NODE_BIN" test-ports.js --json',
        ]);
        $command = $this->rednodeStopCommand($connectivity) . ' >/tmp/resq-rednode-stop-test.log 2>&1; '
            . 'GATEWAY_DIR=' . escapeshellarg($gatewayPath) . ' sh -c ' . escapeshellarg($script);
        $output = trim($this->runRednodeSshCommand($connectivity, $command));
        $result = json_decode($output, true);

        if (! is_array($result)) {
            return response()->json([
                'ok' => false,
                'message' => $output === ''
                    ? 'SSH berhasil, tapi command test port tidak mengeluarkan output. Cek shell logger, path gateway, dan permission script.'
                    : 'Output test port RedNode tidak valid.',
                'output' => $output,
            ], 422);
        }

        if (($result['ok'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Test port RedNode gagal.',
                'output' => $output,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Test port RedNode selesai.',
            'result' => $result,
        ]);
    }

    public function rednodePinScan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'logger_code' => ['required', 'string', 'max:255'],
            'start_slave_id' => ['required', 'integer', 'min:1', 'max:247'],
            'end_slave_id' => ['required', 'integer', 'min:1', 'max:247', 'gte:start_slave_id'],
            'ports' => ['nullable', 'array'],
            'ports.*' => ['string', Rule::in(['/dev/ttyAS4', '/dev/ttyAS5', '/dev/ttyAS2', '/dev/ttyAS3'])],
            'baud_rate' => ['nullable', 'integer', 'min:300', 'max:1000000'],
            'response_timeout_ms' => ['nullable', 'integer', 'min:100', 'max:5000'],
            'delay_between_slaves_ms' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'stop_gateway' => ['nullable', 'boolean'],
        ]);

        $connectivity = $this->rednodeConnectivity($data['logger_code']);

        if (! $connectivity) {
            return response()->json([
                'ok' => false,
                'message' => 'Konfigurasi RedNode belum ditemukan. Apply konfigurasi dulu.',
            ], 422);
        }

        $selectedPorts = collect($data['ports'] ?? [])
            ->filter()
            ->values();
        $portCount = max(1, $selectedPorts->count() ?: 4);
        $slaveCount = ((int) $data['end_slave_id']) - ((int) $data['start_slave_id']) + 1;
        $responseTimeoutMs = (int) ($data['response_timeout_ms'] ?? env('REDNODE_SCAN_RESPONSE_TIMEOUT_MS', 300));
        $delayBetweenSlavesMs = (int) ($data['delay_between_slaves_ms'] ?? env('REDNODE_SCAN_DELAY_MS', 80));
        $timeoutSeconds = (int) min(900, max(60, ceil(($portCount * $slaveCount * ($responseTimeoutMs + $delayBetweenSlavesMs)) / 1000) + 45));
        $logger = $connectivity->relationLoaded('dataLogger')
            ? $connectivity->dataLogger
            : $connectivity->dataLogger()->first();
        $gatewayPath = $connectivity->rednode_gateway_path ?: $logger?->remote_gateway_path ?: env('REDNODE_GATEWAY_PATH', '/root/rednode-gateway');
        $nodeArgs = [
            '--json',
            '--start-slave=' . (int) $data['start_slave_id'],
            '--end-slave=' . (int) $data['end_slave_id'],
            '--data-bits=' . (int) ($connectivity->data_bits ?: env('REDNODE_DATA_BITS', 8)),
            '--stop-bits=' . (int) ($connectivity->stop_bits ?: env('REDNODE_STOP_BITS', 1)),
            '--parity=' . ($connectivity->parity ?: env('REDNODE_PARITY', 'none')),
            '--response-timeout=' . $responseTimeoutMs,
            '--delay-between-slaves=' . $delayBetweenSlavesMs,
        ];

        if (! empty($data['baud_rate'])) {
            $nodeArgs[] = '--baud-rate=' . (int) $data['baud_rate'];
        }

        foreach ($selectedPorts as $port) {
            $nodeArgs[] = '--port=' . $port;
        }

        $terminalLog = [
            '$ ssh ' . $this->rednodeSshLabel($connectivity),
            '[web] Menunggu login SSH dari server aplikasi...',
            '[web] Timeout command: ' . $timeoutSeconds . ' detik',
        ];
        $displayNodeCommand = 'node test-pin-led.js ' . collect($nodeArgs)
            ->map(fn ($arg) => escapeshellarg($arg))
            ->implode(' ');
        $script = implode("\n", [
            'exec 2>&1',
            'echo "[ssh] login berhasil: $(whoami)@$(hostname)"',
            'echo ' . escapeshellarg('$ cd "$GATEWAY_DIR"'),
            'cd "$GATEWAY_DIR" || { echo "[error] Gateway path tidak ditemukan: $GATEWAY_DIR"; exit 1; }',
            'echo ' . escapeshellarg('$ pwd'),
            'pwd',
            'export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:$PATH"',
            'echo ' . escapeshellarg('$ command -v node'),
            'NODE_BIN="$(command -v node || true)"',
            'if [ -z "$NODE_BIN" ]; then echo "[error] node binary tidak ditemukan di PATH"; exit 1; fi',
            'echo "$NODE_BIN"',
            'echo ' . escapeshellarg('$ test -f test-pin-led.js'),
            'if [ ! -f test-pin-led.js ]; then echo "[error] test-pin-led.js tidak ditemukan di $(pwd)"; ls -la; exit 1; fi',
            'echo "[ok] test-pin-led.js ditemukan"',
            'echo ' . escapeshellarg('$ ' . $displayNodeCommand),
            'env ' . $this->rednodeRuntimeEnvString($data['logger_code']) . ' "$NODE_BIN" test-pin-led.js ' . collect($nodeArgs)->map(fn ($arg) => escapeshellarg($arg))->implode(' '),
        ]);
        $scanCommand = 'GATEWAY_DIR=' . escapeshellarg($gatewayPath) . ' sh -c ' . escapeshellarg($script);
        $command = $request->boolean('stop_gateway')
            ? $this->rednodeStopCommand($connectivity) . ' >/tmp/resq-rednode-stop-scan.log 2>&1; '
                . $scanCommand . '; SCAN_STATUS=$?; '
                . $this->rednodeStartCommand($connectivity, $data['logger_code'], $this->rednodeRequestBaseUrl($request)) . ' >/tmp/resq-rednode-restart-scan.log 2>&1; '
                . 'exit $SCAN_STATUS'
            : $scanCommand;
        if ($request->boolean('stop_gateway')) {
            $terminalLog[] = '$ stop gateway sementara';
        }

        try {
            $output = trim($this->runRednodeSshCommand($connectivity, $command, $timeoutSeconds));
        } catch (ValidationException $exception) {
            $rawMessage = collect($exception->errors())->flatten()->first()
                ?: 'Command SSH RedNode gagal.';
            $errorLines = $this->rednodeTerminalOutputLines($rawMessage);
            $message = collect($errorLines)->first(fn ($line) => str_contains($line, '[error]'))
                ?: ($errorLines[0] ?? $rawMessage);
            $terminalLog = array_merge(
                $terminalLog,
                $errorLines ?: ['[error] ' . $rawMessage]
            );

            return response()->json([
                'ok' => false,
                'message' => $message,
                'terminal_log' => $terminalLog,
            ], 422);
        }

        $result = $this->decodeRednodeJsonOutput($output);
        $resultLogs = is_array($result) && is_array($result['logs'] ?? null)
            ? $result['logs']
            : [];
        $terminalLog = array_merge(
            $terminalLog,
            $this->rednodeTerminalOutputLines($output),
            $resultLogs ? array_merge(['', '[script] Output test-pin-led.js:'], $resultLogs) : []
        );

        if (! is_array($result)) {
            return response()->json([
                'ok' => false,
                'message' => $output === ''
                    ? 'SSH berhasil, tapi command scan tidak mengeluarkan output. Cek shell logger, path gateway, node, dan file test-pin-led.js.'
                    : 'Output scan pin RedNode tidak valid.',
                'output' => $output,
                'terminal_log' => array_merge($terminalLog, $output === '' ? ['[error] Command tidak mengeluarkan output.'] : []),
            ], 422);
        }

        if (($result['ok'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Scan pin RedNode gagal.',
                'result' => $result,
                'output' => $output,
                'terminal_log' => $terminalLog,
            ], 422);
        }

        if ($request->boolean('stop_gateway')) {
            $terminalLog[] = '';
            $terminalLog[] = '$ start gateway lagi';
            $terminalLog[] = '[web] Perintah start gateway sudah dikirim setelah scan.';
        }

        return response()->json([
            'ok' => true,
            'message' => 'Scan pin RedNode selesai.',
            'result' => $result,
            'terminal_log' => $terminalLog,
            'gateway_restarted' => $request->boolean('stop_gateway'),
            'timeout_seconds' => $timeoutSeconds,
        ]);
    }

    private function sensorLabel(Sensor $sensor): string
    {
        $type = str_replace('_', ' ', (string) $sensor->type);
        $typeLabel = ucwords($type);

        if ($sensor->type === 'weather_station') {
            $parameters = collect($sensor->weather_parameters ?? [])
                ->map(fn ($parameter) => $this->weatherParameterLabel((string) $parameter))
                ->filter()
                ->implode(', ');

            return trim($typeLabel . ($parameters ? ' - ' . $parameters : ''));
        }

        return trim($typeLabel . ($sensor->parameter ? ' - ' . $sensor->parameter : ''));
    }

    private function weatherParameterLabel(string $parameter): string
    {
        return [
            'temperature' => 'Suhu',
            'humidity' => 'Kelembapan',
            'pressure' => 'Tekanan Udara',
            'wind_speed' => 'Kecepatan Angin',
            'wind_direction' => 'Arah Angin',
            'rainfall' => 'Curah Hujan',
            'solar_radiation' => 'Radiasi Matahari',
            'battery_voltage' => 'Tegangan Baterai',
        ][$parameter] ?? ucwords(str_replace('_', ' ', $parameter));
    }

    private function thresholdExceeded(mixed $value, ?string $threshold): bool
    {
        $numericValue = $this->numericFromText($value);
        $numericThreshold = $this->numericFromText($threshold);

        return $numericValue !== null
            && $numericThreshold !== null
            && $numericValue > $numericThreshold;
    }

    private function syncRednodeHeartbeatSensors(array $heartbeatSensors, ?int $dataLoggerId): void
    {
        foreach ($heartbeatSensors as $item) {
            if (! is_array($item) || empty($item['sensor_code']) || ! empty($item['error'])) {
                continue;
            }

            $sensor = Sensor::where('sensor_code', $item['sensor_code'])->first();

            if (! $sensor) {
                continue;
            }

            $mappingValue = array_key_exists('raw_value', $item) && $item['raw_value'] !== null
                ? $item['raw_value']
                : ($item['value'] ?? null);
            $valueText = array_key_exists('display_value', $item) && $item['display_value'] !== null
                ? (string) $item['display_value']
                : (array_key_exists('value', $item) && $item['value'] !== null
                    ? (string) $item['value']
                    : (array_key_exists('numeric_value', $item) && $item['numeric_value'] !== null ? (string) $item['numeric_value'] : null));

            if ($valueText === null) {
                continue;
            }

            $thresholdExceeded = array_key_exists('threshold_exceeded', $item) && $item['threshold_exceeded'] !== null
                ? (bool) $item['threshold_exceeded']
                : $this->thresholdExceeded($valueText, $sensor->threshold ?? $sensor->rule);
            $level = $thresholdExceeded ? 'Awas' : 'Normal';
            $receivedAt = ! empty($item['received_at']) ? $item['received_at'] : now();

            $sensor->update([
                'value' => $valueText,
                'alert_level' => $level,
                'status' => $level,
                'last_seen_at' => $receivedAt,
            ]);

            $telemetryPayload = [
                'sensor_id' => $sensor->id,
                'data_logger_id' => $dataLoggerId,
                'value' => $sensor->type !== 'weather_station'
                    && $this->canonicalMapping->activeProfileForSensor($sensor)
                    && array_key_exists('raw_value', $item)
                    ? (string) $item['raw_value']
                    : $valueText,
                'alert_level' => $level,
                'status' => $level,
                'received_at' => $receivedAt,
            ];

            if (Schema::hasColumn('telemetry_readings', 'parameter_values')) {
                $telemetryPayload['parameter_values'] = $item['parameter_values'] ?? null;
            }

            $this->upsertTelemetryReading($telemetryPayload);
            $this->canonicalMapping->storeObservation(
                $sensor,
                $mappingValue,
                $dataLoggerId,
                $receivedAt instanceof \DateTimeInterface ? Carbon::parse($receivedAt) : Carbon::parse((string) $receivedAt),
                $item
            );
        }
    }

    private function projectDataLoggers(int $projectId)
    {
        return DataLogger::with(['monitoringStation.workspace'])
            ->whereHas('monitoringStation.workspace', fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('logger_code')
            ->get();
    }

    private function rednodeConnectivity(string $loggerCode): ?ConnectivityConfig
    {
        $logger = DataLogger::where('logger_code', $loggerCode)->first();

        return $logger
            ? ConnectivityConfig::with('dataLogger')
                ->where('data_logger_id', $logger->id)
                ->where(function ($query) {
                    $query->where('protocol', 'Modbus RTU')
                        ->orWhereNotNull('serial_port');
                })
                ->latest()
                ->first()
            : null;
    }

    private function runRednodeSshCommand(ConnectivityConfig $connectivity, string $command, int $timeoutSeconds = 25): string
    {
        $logger = $connectivity->relationLoaded('dataLogger')
            ? $connectivity->dataLogger
            : $connectivity->dataLogger()->first();
        $host = $connectivity->rednode_host ?: $logger?->remote_host ?: env('REDNODE_SSH_HOST');
        $port = (int) ($connectivity->rednode_ssh_port ?: $logger?->remote_ssh_port ?: env('REDNODE_SSH_PORT', 22));
        $user = $connectivity->rednode_ssh_user ?: $logger?->remote_ssh_user ?: env('REDNODE_SSH_USER', 'root');
        $password = $connectivity->rednode_ssh_password ?: $logger?->remote_ssh_password ?: env('REDNODE_SSH_PASSWORD');

        if (! $host || ! $user || ! $password) {
            throw ValidationException::withMessages([
                'rednode_ssh' => 'Isi RedNode Host, SSH User, dan SSH Password dulu.',
            ]);
        }

        $ssh = new SSH2($host, $port, 10);
        $ssh->setTimeout($timeoutSeconds);

        if (! $ssh->login($user, $password)) {
            throw ValidationException::withMessages([
                'rednode_ssh' => 'Login SSH ke RedNode gagal. Cek host, user, atau password.',
            ]);
        }

        $exitMarker = '__RESQ_EXIT_STATUS__';
        $output = $ssh->exec($command . "\nprintf '\\n" . $exitMarker . ":%s\\n' \"$?\"");
        $exitStatus = $ssh->getExitStatus();

        if (preg_match('/\R?' . preg_quote($exitMarker, '/') . ':(-?\d+)\s*$/', $output ?: '', $matches)) {
            $exitStatus = (int) $matches[1];
            $output = preg_replace('/\R?' . preg_quote($exitMarker, '/') . ':-?\d+\s*$/', '', $output ?: '');
        }

        if ($exitStatus === null && ! is_array($this->decodeRednodeJsonOutput($output ?: ''))) {
            $message = trim($output ?: '');
            $message .= ($message === '' ? '' : "\n")
                . sprintf(
                    'Command SSH RedNode belum selesai atau timeout. SSH berhasil login ke %s@%s:%s, tapi proses remote berhenti sebelum exit status diterima.',
                    $user,
                    $host,
                    $port
                );

            throw ValidationException::withMessages([
                'rednode_ssh' => $message,
            ]);
        }

        if (is_int($exitStatus) && $exitStatus !== 0) {
            if (is_array($this->decodeRednodeJsonOutput($output ?: ''))) {
                return $output ?: '';
            }

            $message = trim($output) ?: sprintf(
                'Command SSH RedNode gagal. Exit code: %s. SSH berhasil login ke %s@%s:%s, tapi command di logger gagal tanpa output.',
                $exitStatus,
                $user,
                $host,
                $port
            );

            throw ValidationException::withMessages([
                'rednode_ssh' => $message,
            ]);
        }

        return $output ?: '';
    }

    private function decodeRednodeJsonOutput(string $output): ?array
    {
        $decoded = json_decode(trim($output), true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $lines = array_reverse(preg_split('/\R/', trim($output)) ?: []);

        foreach ($lines as $line) {
            $line = trim($line);

            if (! str_starts_with($line, '{') || ! str_ends_with($line, '}')) {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function rednodeSshLabel(ConnectivityConfig $connectivity): string
    {
        $logger = $connectivity->relationLoaded('dataLogger')
            ? $connectivity->dataLogger
            : $connectivity->dataLogger()->first();
        $host = $connectivity->rednode_host ?: $logger?->remote_host ?: env('REDNODE_SSH_HOST') ?: '(host-belum-diisi)';
        $port = (int) ($connectivity->rednode_ssh_port ?: $logger?->remote_ssh_port ?: env('REDNODE_SSH_PORT', 22));
        $user = $connectivity->rednode_ssh_user ?: $logger?->remote_ssh_user ?: env('REDNODE_SSH_USER', 'root');

        return $user . '@' . $host . ':' . $port;
    }

    private function rednodeTerminalOutputLines(string $output): array
    {
        return collect(preg_split('/\R/', trim($output)) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->reject(fn ($line) => $line === '')
            ->reject(fn ($line) => str_starts_with($line, '{') && str_ends_with($line, '}'))
            ->values()
            ->all();
    }

    private function rednodeRequestBaseUrl(Request $request): string
    {
        $configuredUrl = $this->rednodeSetting('REDNODE_PUBLIC_APP_URL') ?: config('app.url');
        $requestUrl = $request->getSchemeAndHttpHost();

        if ($configuredUrl && ! $this->rednodeIsLocalUrl($configuredUrl)) {
            return rtrim((string) $configuredUrl, '/');
        }

        if ($requestUrl && ! $this->rednodeIsLocalUrl($requestUrl)) {
            return rtrim((string) $requestUrl, '/');
        }

        return rtrim((string) ($configuredUrl ?: $requestUrl), '/');
    }

    private function rednodePublicAppUrl(): string
    {
        return rtrim((string) ($this->rednodeSetting('REDNODE_PUBLIC_APP_URL') ?: config('app.url')), '/');
    }

    private function rednodeIsLocalUrl(string $url): bool
    {
        return str_contains($url, '127.0.0.1') || str_contains($url, 'localhost');
    }

    private function rednodeSetting(string $key, mixed $default = null): mixed
    {
        $value = env($key);

        if ($value !== null && $value !== '') {
            return $value;
        }

        foreach ([$_ENV[$key] ?? null, $_SERVER[$key] ?? null] as $candidate) {
            if ($candidate !== null && $candidate !== '') {
                return $candidate;
            }
        }

        $envPath = base_path('.env');

        if (! is_readable($envPath)) {
            return $default;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, $key . '=')) {
                continue;
            }

            $rawValue = trim(substr($line, strlen($key) + 1));
            $parsedValue = trim($rawValue, "\"'");

            return $parsedValue === '' ? $default : $parsedValue;
        }

        return $default;
    }

    private function rednodeStartCommand(ConnectivityConfig $connectivity, string $loggerCode, ?string $appUrl = null): string
    {
        $logger = $connectivity->relationLoaded('dataLogger')
            ? $connectivity->dataLogger
            : $connectivity->dataLogger()->first();
        $gatewayPath = $connectivity->rednode_gateway_path ?: $logger?->remote_gateway_path ?: env('REDNODE_GATEWAY_PATH', '/root/rednode-gateway');
        $runtimeEnv = $this->rednodeRuntimeEnvArray($loggerCode, $appUrl);
        $exportCommands = collect($runtimeEnv)
            ->map(fn ($value, $key) => 'export ' . $key . '=' . escapeshellarg((string) $value))
            ->values()
            ->all();
        $script = implode("\n", [
            'echo "[web] mulai proses remote gateway"',
            'echo "[ssh] login berhasil: $(whoami)@$(hostname)"',
            'echo ' . escapeshellarg('$ cd "$GATEWAY_DIR"'),
            'cd "$GATEWAY_DIR" || { echo "[error] Gateway path tidak ditemukan: $GATEWAY_DIR"; exit 1; }',
            'echo ' . escapeshellarg('$ pwd'),
            'pwd',
            'export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:$PATH"',
            'echo ' . escapeshellarg('$ command -v node'),
            'NODE_BIN="$(command -v node || true)"',
            'NPM_BIN="$(command -v npm || true)"',
            'if [ -z "$NODE_BIN" ]; then echo "node binary not found"; exit 1; fi',
            'echo "$NODE_BIN"',
            'echo ' . escapeshellarg('$ command -v npm'),
            'if [ -n "$NPM_BIN" ]; then echo "$NPM_BIN"; else echo "npm not found"; fi',
            'if [ ! -f gateway.js ]; then echo "gateway.js not found in $(pwd)"; exit 1; fi',
            'echo ' . escapeshellarg('$ runtime env'),
            'echo ' . escapeshellarg('$ export REDNODE_CONFIG_URL=' . $runtimeEnv['REDNODE_CONFIG_URL']),
            'echo ' . escapeshellarg('$ export REDNODE_CALLBACK_URL=' . $runtimeEnv['REDNODE_CALLBACK_URL']),
            'echo ' . escapeshellarg('$ export REDNODE_HEARTBEAT_URL=' . $runtimeEnv['REDNODE_HEARTBEAT_URL']),
            'echo ' . escapeshellarg('$ export REDNODE_LOGGER_CODE=' . $runtimeEnv['REDNODE_LOGGER_CODE']),
            'echo ' . escapeshellarg('$ export APP_URL=' . $runtimeEnv['APP_URL']),
            ...$exportCommands,
            ': > gateway.log',
            'if [ -f package.json ] && [ -n "$NPM_BIN" ] && grep -q \'"gateway"\' package.json; then',
            '  echo ' . escapeshellarg('$ npm run gateway'),
            '  nohup "$NPM_BIN" run gateway >> gateway.log 2>&1 < /dev/null &',
            'else',
            '  echo ' . escapeshellarg('$ node gateway.js'),
            '  nohup "$NODE_BIN" gateway.js >> gateway.log 2>&1 < /dev/null &',
            'fi',
            'echo $! > gateway.pid',
            'echo "[web] menunggu output awal gateway..."',
            'sleep 4',
            'if kill -0 "$(cat gateway.pid)" 2>/dev/null; then echo "started pid=$(cat gateway.pid) node=$NODE_BIN npm=${NPM_BIN:-not-found}"; echo "$ tail -n 40 gateway.log"; tail -n 40 gateway.log; exit 0; fi',
            'echo "failed to start"',
            'tail -n 40 gateway.log',
            'exit 1',
        ]);

        return 'GATEWAY_DIR=' . escapeshellarg($gatewayPath) . ' sh -c ' . escapeshellarg($script);
    }

    private function rednodeRuntimeEnvString(string $loggerCode, ?string $appUrl = null): string
    {
        return collect($this->rednodeRuntimeEnvArray($loggerCode, $appUrl))
            ->map(fn ($value, $key) => $key . '=' . escapeshellarg((string) $value))
            ->implode(' ');
    }

    private function rednodeRuntimeEnvArray(string $loggerCode, ?string $appUrl = null): array
    {
        $appUrl = rtrim($appUrl ?: $this->rednodePublicAppUrl(), '/');

        return [
            'APP_URL' => $appUrl,
            'REDNODE_CONFIG_URL' => $this->rednodeSetting('REDNODE_CONFIG_URL') ?: $appUrl . '/api/rednode/config',
            'REDNODE_CALLBACK_URL' => $this->rednodeSetting('REDNODE_CALLBACK_URL') ?: $appUrl . '/api/realtime-sensor-status',
            'REDNODE_HEARTBEAT_URL' => $this->rednodeSetting('REDNODE_HEARTBEAT_URL') ?: $appUrl . '/api/rednode/heartbeat',
            'REDNODE_LOGGER_CODE' => $loggerCode,
            'REDNODE_CONFIG_REFRESH_MS' => (string) env('REDNODE_CONFIG_REFRESH_MS', 5000),
            'REDNODE_HTTP_TIMEOUT_MS' => (string) env('REDNODE_HTTP_TIMEOUT_MS', 10000),
        ];
    }

    private function rednodeStopCommand(ConnectivityConfig $connectivity): string
    {
        $logger = $connectivity->relationLoaded('dataLogger')
            ? $connectivity->dataLogger
            : $connectivity->dataLogger()->first();
        $gatewayPath = $connectivity->rednode_gateway_path ?: $logger?->remote_gateway_path ?: env('REDNODE_GATEWAY_PATH', '/root/rednode-gateway');
        $script = implode("\n", [
            'echo "[web] stop semua proses gateway lama jika ada"',
            'echo ' . escapeshellarg('$ cd "$GATEWAY_DIR"'),
            'cd "$GATEWAY_DIR" || { echo "[error] Gateway path tidak ditemukan: $GATEWAY_DIR"; exit 1; }',
            'export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:$PATH"',
            'STOPPED=0',
            'STOP_FILE="/tmp/resq-gateway-stopped.$$"',
            'rm -f "$STOP_FILE"',
            'SELF_PID="$$"',
            'PARENT_PID="$PPID"',
            'stop_pid() {',
            '  PID="$1"',
            '  [ -z "$PID" ] && return',
            '  [ "$PID" = "$SELF_PID" ] && return',
            '  [ "$PID" = "$PARENT_PID" ] && return',
            '  CMD="$(ps -p "$PID" -o args= 2>/dev/null || true)"',
            '  [ -z "$CMD" ] && return',
            '  case "$CMD" in',
            '    *"node gateway.js"*|*"node "*"gateway.js"*|*"npm run gateway"*|*"/npm "*"run gateway"*) ;;',
            '    *) echo "skip pid=$PID cmd=$CMD"; return ;;',
            '  esac',
            '  kill "$PID" 2>/dev/null || true',
            '  sleep 1',
            '  kill -9 "$PID" 2>/dev/null || true',
            '  STOPPED=1',
            '  touch "$STOP_FILE"',
            '  echo "stopped pid=$PID"',
            '}',
            'if [ -f gateway.pid ]; then',
            '  echo ' . escapeshellarg('$ stop gateway.pid'),
            '  PID="$(cat gateway.pid 2>/dev/null || true)"',
            '  if kill -0 "$PID" 2>/dev/null; then stop_pid "$PID"; fi',
            '  rm -f gateway.pid',
            'fi',
            'echo ' . escapeshellarg('$ cari proses gateway lama'),
            'ps -eo pid=,args= 2>/dev/null | while IFS= read -r LINE; do',
            '  PID="$(echo "$LINE" | awk \'{print $1}\')"',
            '  CMD="${LINE#*$PID }"',
            '  case "$CMD" in',
            '    *"node gateway.js"*|*"node "*"gateway.js"*|*"npm run gateway"*|*"/npm "*"run gateway"*) stop_pid "$PID" ;;',
            '  esac',
            'done',
            'if [ "$STOPPED" = "1" ] || [ -f "$STOP_FILE" ]; then echo "semua pid gateway lama sudah dihentikan"; else echo "not running"; fi',
            'rm -f "$STOP_FILE"',
        ]);

        return 'GATEWAY_DIR=' . escapeshellarg($gatewayPath) . ' sh -c ' . escapeshellarg($script);
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

    private function numericFromText(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        preg_match('/-?\d+(\.\d+)?/', str_replace(',', '.', (string) $value), $matches);

        return isset($matches[0]) ? (float) $matches[0] : null;
    }

    private function valueWithUnit(mixed $value, ?string $unit): string
    {
        $text = trim((string) ($value ?? '-'));
        $unit = trim((string) $unit);

        if ($text === '' || $text === '-' || $unit === '' || $unit === '0') {
            return $text === '' ? '-' : $text;
        }

        if (! preg_match('/-?\d+([,.]\d+)?/', $text)) {
            return $text;
        }

        return Str::endsWith(Str::lower($text), Str::lower($unit))
            ? $text
            : trim($text . ' ' . $unit);
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
        if ($type === 'data-logger') {
            $this->cleanupDataLoggerReferences($id);
        }

        $models[$type]::findOrFail($id)->delete();

        return back()->with('message', 'Data berhasil dihapus.');
    }

    private function cleanupDataLoggerReferences(int $dataLoggerId): void
    {
        Sensor::where('data_logger_id', $dataLoggerId)->update(['data_logger_id' => null]);
        ConnectivityConfig::where('data_logger_id', $dataLoggerId)->delete();
        DeviceCredential::where('data_logger_id', $dataLoggerId)->delete();
        TelemetryReading::where('data_logger_id', $dataLoggerId)->update(['data_logger_id' => null]);
    }
}
