<?php

namespace App\Http\Controllers;

use App\Models\MqttConfiguration;
use App\Models\Project;
use App\Models\CanonicalParameter;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Services\AuthorizationService;
use App\Services\CanonicalMappingService;
use App\Services\MqttCredentialCipher;
use App\Services\MqttOutboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class MqttConfigurationController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly MqttCredentialCipher $cipher,
        private readonly CanonicalMappingService $canonicalMapping,
        private readonly MqttOutboxService $outbox
    ) {}

    public function index(Request $request): View
    {
        $projects = $this->authorization->scopeProjectsForUser($request->user(), Project::query())->orderBy('name')->get();
        $configurations = MqttConfiguration::with(['project', 'sensors', 'nodeRedDataLoggers'])
            ->whereIn('project_id', $projects->pluck('id'))
            ->latest()
            ->get()
            ->each(function (MqttConfiguration $config) {
                $loggerCodes = $config->nodeRedDataLoggers
                    ->where('logger_status', '!=', 'Inactive')
                    ->pluck('logger_code')
                    ->filter()
                    ->unique()
                    ->values();

                $config->setAttribute('current_logger_codes', $loggerCodes->all());
                $config->setAttribute('current_sensor_count', $this->mqttConfigurationSensorCount($config));
                $config->setAttribute('is_current', $loggerCodes->isNotEmpty());
            })
            ->sortByDesc(fn (MqttConfiguration $config) => sprintf(
                '%d|%010d|%010d|%010d',
                $config->getAttribute('is_current') ? 1 : 0,
                optional($config->last_received_at)->timestamp ?? 0,
                optional($config->last_connected_at)->timestamp ?? 0,
                $config->id
            ))
            ->values();

        $canonicalParameters = CanonicalParameter::orderBy('field_identity')->get();

        return view('modules.mqtt-configurations.index', compact('projects', 'configurations', 'canonicalParameters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'configuration_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'broker_url' => ['required', 'string', 'max:2048', 'regex:/^(mqtt|mqtts|ws|wss):\/\//i'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:4096'],
            'consumer_enabled' => ['nullable', 'boolean'],
            'consumer_topic' => ['nullable', 'required_if:consumer_enabled,1', 'string', 'max:1024'],
            'consumer_qos' => ['required', 'integer', 'min:0', 'max:2'],
            'example_payload' => ['nullable', 'json'],
            'sensor_code_path' => ['nullable', 'string', 'max:255'],
            'producer_enabled' => ['nullable', 'boolean'],
            'producer_topic' => ['nullable', 'required_if:producer_enabled,1', 'string', 'max:1024'],
            'producer_qos' => ['required', 'integer', 'min:0', 'max:2'],
            'producer_retain' => ['nullable', 'boolean'],
            'publish_canonical' => ['nullable', 'boolean'],
            'publish_warning' => ['nullable', 'boolean'],
            'canonical_parameter_ids' => ['nullable', 'array'],
            'canonical_parameter_ids.*' => ['integer', 'exists:canonical_parameters,id'],
            'warning_levels' => ['nullable', 'array'],
            'warning_levels.*' => [Rule::in(['Normal', 'Waspada', 'Siaga', 'Awas'])],
            'canonical_template' => ['nullable', 'required_if:publish_canonical,1', 'string'],
            'warning_template' => ['nullable', 'required_if:publish_warning,1', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $existing = MqttConfiguration::where('configuration_code', $data['configuration_code'])->first();
        abort_if($existing && (int) $existing->project_id !== (int) $project->id, 403);
        abort_unless($this->authorization->canAccessProject($request->user(), $project)
            && $this->authorization->canMutateAssetRegistry($request->user()), 403);

        $data['consumer_enabled'] = $request->boolean('consumer_enabled');
        $data['producer_enabled'] = $request->boolean('producer_enabled');
        $data['producer_retain'] = $request->boolean('producer_retain');
        $data['publish_canonical'] = $request->boolean('publish_canonical');
        $data['publish_warning'] = $request->boolean('publish_warning');
        $data['is_active'] = $request->boolean('is_active');
        if (! $data['consumer_enabled'] && ! $data['producer_enabled']) {
            throw ValidationException::withMessages(['consumer_enabled' => 'Aktifkan consumer atau producer.']);
        }

        foreach (['canonical_template', 'warning_template'] as $field) {
            if (! empty($data[$field])) {
                try {
                    $this->outbox->validateTemplate($data[$field]);
                } catch (InvalidArgumentException $error) {
                    throw ValidationException::withMessages([$field => $error->getMessage()]);
                }
            }
        }

        if ($data['consumer_enabled']) {
            if (! empty($data['example_payload'])) {
                $example = json_decode($data['example_payload'], true, flags: JSON_THROW_ON_ERROR);
                if (! empty($data['sensor_code_path']) && data_get($example, $data['sensor_code_path']) === null) {
                    throw ValidationException::withMessages(['sensor_code_path' => 'Path tidak ditemukan pada example payload.']);
                }
                $data['example_payload'] = $example;
            } else {
                $data['example_payload'] = null;
            }
        } else {
            $data['example_payload'] = null;
        }

        try {
            if (! empty($data['password'])) {
                $data['password_ciphertext'] = $this->cipher->encrypt($data['password']);
            }
        } catch (RuntimeException $error) {
            throw ValidationException::withMessages(['password' => $error->getMessage()]);
        }
        unset($data['password']);

        MqttConfiguration::updateOrCreate(['configuration_code' => $data['configuration_code']], $data);

        return back()->with('message', 'MQTT configuration berhasil disimpan.');
    }

    public function destroy(Request $request, MqttConfiguration $configuration): RedirectResponse
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $configuration->project_id)
            && $this->authorization->canMutateAssetRegistry($request->user()), 403);
        if ($configuration->sensors()->exists()) {
            return back()->withErrors(['mqtt_configuration' => 'Configuration masih dipakai sensor. Lepaskan terlebih dahulu.']);
        }
        if ($configuration->nodeRedDataLoggers()->where('logger_status', '!=', 'Inactive')->exists()) {
            return back()->withErrors(['mqtt_configuration' => 'Configuration masih dipakai Data Logger aktif. Lepaskan dari Data Logger terlebih dahulu.']);
        }
        $configuration->delete();

        return back()->with('message', 'MQTT configuration berhasil dihapus.');
    }

    public function status(Request $request): JsonResponse
    {
        $projects = $this->authorization->scopeProjectsForUser($request->user(), Project::query())->pluck('id');
        $rows = MqttConfiguration::whereIn('project_id', $projects)->get()->map(fn ($config) => [
            'id' => $config->id,
            'status' => $config->connection_status,
            'last_connected_at' => optional($config->last_connected_at)->toISOString(),
            'last_received_at' => optional($config->last_received_at)->toISOString(),
            'last_published_at' => optional($config->last_published_at)->toISOString(),
            'last_error' => $config->last_error,
            'metrics' => $config->runtime_metrics ?? [],
        ]);

        return response()->json(['ok' => true, 'configurations' => $rows]);
    }

    public function test(Request $request, MqttConfiguration $configuration): JsonResponse
    {
        abort_unless($this->authorization->canAccessProject($request->user(), $configuration->project_id), 403);
        $gateway = rtrim((string) env('MODBUS_BACKEND_URL'), '/');
        if ($gateway === '') {
            return response()->json(['ok' => false, 'message' => 'MODBUS_BACKEND_URL belum dikonfigurasi.'], 422);
        }

        try {
            $response = Http::timeout(12)->post("{$gateway}/api/mqtt/configurations/{$configuration->id}/test");
        } catch (\Throwable $error) {
            return response()->json(['ok' => false, 'message' => 'Gateway MQTT tidak dapat dihubungi: '.$error->getMessage()], 502);
        }

        return response()->json($response->json() ?: ['ok' => false, 'message' => 'Respons gateway tidak valid.'], $response->status());
    }

    public function ingest(Request $request): JsonResponse
    {
        $token = env('MQTT_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN');
        if ($token && ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['ok' => false, 'message' => 'Token MQTT tidak valid.'], 401);
        }

        $data = $request->validate([
            'mqtt_configuration_id' => ['required', 'exists:mqtt_configurations,id'],
            'sensor_code' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:1024'],
            'payload' => ['required', 'array'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.source_path' => ['required', 'string', 'max:255'],
            'values.*.value' => ['present'],
            'observed_at' => ['nullable', 'date'],
        ]);

        $config = MqttConfiguration::findOrFail($data['mqtt_configuration_id']);
        abort_unless($config->is_active && $config->consumer_enabled, 409);
        $sensor = $this->resolveIncomingSensor($config, $data['sensor_code']);
        abort_unless((int) $sensor->workspace?->project_id === (int) $config->project_id, 422);

        $observedAt = ! empty($data['observed_at']) ? Carbon::parse($data['observed_at']) : now();
        $observation = null;
        foreach ($data['values'] as $item) {
            $profile = $sensor->mappingProfiles->firstWhere('source_parameter', $item['source_path']);
            if (! $profile || ! $profile->canonicalParameter) {
                continue;
            }
            $observation = $this->canonicalMapping->storeObservation(
                $sensor,
                $item['value'],
                null,
                $observedAt,
                ['mqtt_configuration_id' => $config->id, 'topic' => $data['topic'], 'payload' => $data['payload']],
                $profile
            ) ?: $observation;
        }
        if (! $observation) {
            throw ValidationException::withMessages(['values' => 'Tidak ada source_path yang cocok dengan canonical mapping sensor.']);
        }

        $primary = $data['values'][0]['value'] ?? null;
        $previousLevel = $sensor->alert_level ?: 'Normal';
        $numeric = is_numeric($primary) ? (float) $primary : null;
        $threshold = is_numeric($sensor->threshold) ? (float) $sensor->threshold : null;
        $level = $numeric !== null && $threshold !== null && $numeric >= $threshold ? 'Awas' : 'Normal';

        // Build parameter_values from the incoming payload to preserve multi-parameter display
        $incomingParameterValues = $data['payload']['parameter_values'] ?? [];
        if (empty($incomingParameterValues)) {
            // Fallback: build from the matched values array
            $incomingParameterValues = collect($data['values'])->map(fn ($item) => [
                'parameter' => $item['source_path'] ?? null,
                'value' => $item['value'] ?? null,
                'value_text' => is_numeric($item['value'] ?? null)
                    ? (string) $item['value']
                    : ($item['value'] ?? null),
            ])->all();
        }

        // Use the full display value from payload if available (e.g. RedNode sends value_text with all params)
        $payloadDisplayValue = $data['payload']['value_text'] ?? $data['payload']['value'] ?? null;
        $sensorDisplayValue = $payloadDisplayValue ?: (is_scalar($primary) ? (string) $primary : json_encode($primary));

        // Only update sensor.value if the incoming display value is richer or same
        // (avoid overwriting a multi-parameter display string with a bare numeric)
        $currentSensorValue = $sensor->value;
        $shouldUpdateValue = $payloadDisplayValue
            || !$currentSensorValue
            || strlen((string) $sensorDisplayValue) >= strlen((string) $currentSensorValue);

        if ($shouldUpdateValue) {
            $sensor->update(['value' => $sensorDisplayValue, 'alert_level' => $level, 'status' => $level, 'last_seen_at' => now()]);
        } else {
            $sensor->update(['alert_level' => $level, 'status' => $level, 'last_seen_at' => now()]);
        }

        $readingPayload = [
            'sensor_id' => $sensor->id,
            'data_logger_id' => $sensor->data_logger_id,
            'value' => $sensorDisplayValue,
            'alert_level' => $level,
            'status' => $level,
            'received_at' => now(),
        ];

        if (\Schema::hasColumn('telemetry_readings', 'parameter_values') && !empty($incomingParameterValues)) {
            $readingPayload['parameter_values'] = $incomingParameterValues;
        }

        if (\Schema::hasColumn('telemetry_readings', 'numeric_value') && $numeric !== null) {
            $readingPayload['numeric_value'] = $numeric;
        }

        if (\Schema::hasColumn('telemetry_readings', 'raw_value') && isset($data['payload']['raw'])) {
            $readingPayload['raw_value'] = (string) $data['payload']['raw'];
        }

        if (\Schema::hasColumn('telemetry_readings', 'registers') && isset($data['payload']['registers'])) {
            $readingPayload['registers'] = $data['payload']['registers'];
        }

        $reading = TelemetryReading::create($readingPayload);
        $this->outbox->enqueueCanonical($observation->fresh(), $sensor);
        $this->outbox->enqueueWarning($sensor, $previousLevel, $level, $reading->id);

        return response()->json(['ok' => true, 'sensor_id' => $sensor->id, 'canonical_observation_id' => $observation->id]);
    }

    private function resolveIncomingSensor(MqttConfiguration $config, string $sensorCode): Sensor
    {
        return Sensor::with(['workspace.project', 'mappingProfiles.canonicalParameter', 'dataLogger'])
            ->where('sensor_code', $sensorCode)
            ->where(function ($query) use ($config) {
                $query->where(function ($nested) use ($config) {
                    $nested->where('mqtt_configuration_id', $config->id)
                        ->where('input_source', 'mqtt');
                })->orWhereHas('dataLogger', function ($nested) use ($config) {
                    $nested->where('node_red_mqtt_configuration_id', $config->id)
                        ->where('logger_status', '!=', 'Inactive');
                });
            })
            ->orderByRaw('CASE WHEN mqtt_configuration_id = ? AND input_source = ? THEN 0 ELSE 1 END', [$config->id, 'mqtt'])
            ->firstOrFail();
    }

    private function mqttConfigurationSensorCount(MqttConfiguration $config): int
    {
        return Sensor::query()
            ->where(function ($query) use ($config) {
                $query->where(function ($nested) use ($config) {
                    $nested->where('mqtt_configuration_id', $config->id)
                        ->where('input_source', 'mqtt');
                })->orWhereHas('dataLogger', function ($nested) use ($config) {
                    $nested->where('node_red_mqtt_configuration_id', $config->id)
                        ->where('logger_status', '!=', 'Inactive');
                });
            })
            ->count();
    }

    /**
     * Check if MQTT gateway process is running.
     */
    public function gatewayStatus(): JsonResponse
    {
        $gatewayUrl = $this->gatewayBaseUrl();

        try {
            $response = Http::timeout(3)->get("{$gatewayUrl}/health");

            if ($response->ok()) {
                $data = $response->json();

                return response()->json([
                    'ok' => true,
                    'running' => true,
                    'mqtt' => $data['mqtt'] ?? null,
                    'stats' => $data['stats'] ?? null,
                    'configurations' => $data['mqtt']['configurations'] ?? [],
                ]);
            }
        } catch (\Throwable $e) {
            // Gateway not running
        }

        return response()->json([
            'ok' => true,
            'running' => false,
            'mqtt' => null,
            'stats' => null,
        ]);
    }

    /**
     * Start the MQTT gateway process.
     */
    public function gatewayStart(Request $request): JsonResponse
    {
        $gatewayUrl = $this->gatewayBaseUrl();

        // Check if already running
        try {
            $health = Http::timeout(3)->get("{$gatewayUrl}/health");
            if ($health->ok()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'MQTT Gateway sudah running.',
                    'already_running' => true,
                ]);
            }
        } catch (\Throwable $e) {
            // Not running, proceed to start
        }

        $projectRoot = base_path();
        $nodeScript = $projectRoot . '/modbus-server/server.js';
        $logFile = storage_path('logs/mqtt-gateway.log');

        if (! file_exists($nodeScript)) {
            return response()->json([
                'ok' => false,
                'message' => 'File gateway tidak ditemukan: modbus-server/server.js',
            ], 422);
        }

        // Start the gateway process in background
        $command = sprintf(
            'cd %s && nohup node %s >> %s 2>&1 & echo $!',
            escapeshellarg($projectRoot),
            escapeshellarg($nodeScript),
            escapeshellarg($logFile)
        );

        $pid = trim(shell_exec($command) ?? '');

        // Wait a moment and verify
        usleep(1500000); // 1.5 seconds

        try {
            $health = Http::timeout(3)->get("{$gatewayUrl}/health");
            if ($health->ok()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'MQTT Gateway berhasil dijalankan.',
                    'pid' => $pid,
                ]);
            }
        } catch (\Throwable $e) {
            // Still not responding
        }

        return response()->json([
            'ok' => false,
            'message' => 'Gateway dimulai (PID: ' . $pid . ') tapi belum merespons. Cek log: storage/logs/mqtt-gateway.log',
            'pid' => $pid,
        ], 422);
    }

    /**
     * Stop the MQTT gateway process.
     */
    public function gatewayStop(): JsonResponse
    {
        $gatewayUrl = $this->gatewayBaseUrl();
        $port = (int) (config('services.modbus.port') ?: env('MODBUS_BACKEND_PORT', 3100));

        // Find and kill process on the gateway port
        $pid = trim(shell_exec("lsof -ti :{$port} 2>/dev/null") ?? '');

        if ($pid === '') {
            return response()->json([
                'ok' => true,
                'message' => 'MQTT Gateway tidak sedang berjalan.',
                'was_running' => false,
            ]);
        }

        // Kill the process
        $pids = array_filter(explode("\n", $pid));
        foreach ($pids as $p) {
            if (is_numeric(trim($p))) {
                posix_kill((int) trim($p), SIGTERM);
            }
        }

        usleep(500000); // 0.5 seconds

        return response()->json([
            'ok' => true,
            'message' => 'MQTT Gateway dihentikan.',
            'was_running' => true,
            'killed_pids' => $pids,
        ]);
    }

    /**
     * Restart the MQTT gateway (stop then start).
     */
    public function gatewayRestart(Request $request): JsonResponse
    {
        $this->gatewayStop();
        usleep(1000000); // 1 second

        return $this->gatewayStart($request);
    }

    private function gatewayBaseUrl(): string
    {
        return rtrim(
            config('services.modbus.url')
                ?: env('MODBUS_BACKEND_URL', 'http://127.0.0.1:3100'),
            '/'
        );
    }
}
