<?php

namespace App\Http\Controllers;

use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Province;
use App\Models\ResponsePlan;
use App\Models\CanonicalParameter;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\TelemetryReading;
use App\Models\WarningStation;
use App\Services\CanonicalMappingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectSetupController extends Controller
{
    public function __construct(private readonly CanonicalMappingService $canonicalMapping)
    {
    }

    public function index(): View
    {
        return view('modules.projects.index', $this->viewData());
    }

    public function monitoring(): View
    {
        return view('modules.projects.monitoring', $this->viewData());
    }

    public function startMonitoring(Request $request)
    {
        $data = $request->validate(['project_id' => 'required|exists:resq_projects,id']);
        $project = Project::findOrFail($data['project_id']);
        $loggers = $this->projectDataLoggers($project)
            ->map(fn (DataLogger $dataLogger) => $this->setMonitoringRuntime($dataLogger, true))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'message' => count($loggers)
                ? 'Monitoring project berhasil dimulai.'
                : 'Monitoring project dimulai, tetapi belum ada logger pada project ini.',
            'loggers' => $loggers,
        ]);
    }

    public function stopMonitoring(Request $request)
    {
        $data = $request->validate(['project_id' => 'required|exists:resq_projects,id']);
        $project = Project::findOrFail($data['project_id']);
        $loggers = $this->projectDataLoggers($project)
            ->map(fn (DataLogger $dataLogger) => $this->setMonitoringRuntime($dataLogger, false))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'message' => 'Monitoring project berhasil dihentikan.',
            'loggers' => $loggers,
        ]);
    }

    public function liveMonitoring(Request $request)
    {
        $data = $request->validate(['project_id' => 'nullable|exists:resq_projects,id']);

        if (empty($data['project_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Project tidak dipilih.',
                'summary' => ['loggers' => 0, 'online_loggers' => 0, 'sensors' => 0, 'fresh_sensors' => 0],
                'sensors' => [],
                'generated_at' => now()->toISOString(),
            ]);
        }

        $project = Project::findOrFail($data['project_id']);
        $workspaces = $project->workspaces ?? collect();

        $projectSensors = collect();
        $loggerIds = [];
        $loggerById = collect();
        $sensorLoggerFallbacks = [];

        foreach ($workspaces as $workspace) {
            foreach ($workspace->monitoringStations ?? collect() as $station) {
                $stationLoggers = collect($station->dataLoggers ?? []);
                foreach ($station->dataLoggers ?? collect() as $dataLogger) {
                    $loggerIds[] = $dataLogger->id;
                    $loggerById->put($dataLogger->id, $dataLogger);
                }

                foreach ($station->sensors ?? collect() as $sensor) {
                    $projectSensors->push($sensor);
                    $fallbackLogger = $sensor->dataLogger ?? $stationLoggers->first();
                    if ($fallbackLogger) {
                        $sensorLoggerFallbacks[$sensor->id] = $fallbackLogger->id;
                        $loggerIds[] = $fallbackLogger->id;
                        $loggerById->put($fallbackLogger->id, $fallbackLogger);
                    }
                }
            }
        }

        $loggerIds = array_values(array_unique($loggerIds));
        $sensorIds = $projectSensors->pluck('id')->all();
        $onlineLoggerIds = $this->onlineLoggerIds($loggerIds);

        $latestReadings = TelemetryReading::with(['sensor.monitoringStation', 'dataLogger'])
            ->when(! empty($sensorIds), fn ($q) => $q->whereIn('sensor_id', $sensorIds))
            ->latest('received_at')
            ->latest()
            ->limit(500)
            ->get()
            ->unique('sensor_id')
            ->keyBy('sensor_id');

        $freshWindowSeconds = 30;
        $freshCount = 0;
        $sensors = [];

        foreach ($projectSensors as $sensor) {
            $reading = $latestReadings->get($sensor->id);
            $receivedAt = $reading?->received_at;

            $isFresh = $receivedAt
                ? $receivedAt->greaterThanOrEqualTo(now()->subSeconds($freshWindowSeconds))
                : false;

            $loggerId = $reading?->data_logger_id
                ?? $sensor->data_logger_id
                ?? ($sensorLoggerFallbacks[$sensor->id] ?? null);

            if ($isFresh && $loggerId && ! in_array($loggerId, $onlineLoggerIds)) {
                $onlineLoggerIds[] = $loggerId;
            }

            if ($isFresh) {
                $freshCount++;
            }

            $sensors[] = [
                'id' => $sensor->id,
                'logger_code' => $reading?->dataLogger?->logger_code
                    ?? $sensor->dataLogger?->logger_code
                    ?? $loggerById->get($loggerId)?->logger_code
                    ?? '-',
                'station' => $sensor->monitoringStation?->name ?? '-',
                'sensor_code' => $sensor->sensor_code,
                'sensor_label' => $sensor->parameter,
                'sensor_type' => $sensor->type,
                'value' => $this->liveSensorValue($sensor, $reading?->value ?? $sensor->value),
                'status' => $reading?->status ?? $sensor->status,
                'parameter_values' => $this->liveParameterValues($sensor, $reading?->value ?? $sensor->value),
                'received_at' => optional($receivedAt)->toISOString(),
                'fresh' => $isFresh,
                'online' => $loggerId ? in_array($loggerId, $onlineLoggerIds) : false,
            ];
        }

        return response()->json([
            'ok' => true,
            'summary' => [
                'loggers' => count($loggerIds),
                'online_loggers' => count(array_unique($onlineLoggerIds)),
                'sensors' => count($sensorIds),
                'fresh_sensors' => $freshCount,
            ],
            'sensors' => $sensors,
            'generated_at' => now()->toISOString(),
        ]);
    }

    private function projectDataLoggers(Project $project)
    {
        $loggers = collect();

        foreach ($project->workspaces ?? collect() as $workspace) {
            foreach ($workspace->monitoringStations ?? collect() as $station) {
                foreach ($station->dataLoggers ?? collect() as $dataLogger) {
                    $loggers->push($dataLogger);
                }

                foreach ($station->sensors ?? collect() as $sensor) {
                    if ($sensor->dataLogger) {
                        $loggers->push($sensor->dataLogger);
                    }
                }
            }
        }

        return $loggers->unique('id')->values();
    }

    private function onlineLoggerIds(array $loggerIds): array
    {
        if (empty($loggerIds) || ! Schema::hasTable('connectivity_configs')) {
            return [];
        }

        return ConnectivityConfig::whereIn('data_logger_id', $loggerIds)
            ->where('protocol', 'Modbus RTU')
            ->get()
            ->filter(function (ConnectivityConfig $connectivity) {
                $runtime = $connectivity->runtime_state ?? [];
                $lastSeenAt = $runtime['last_seen_at'] ?? null;

                return $lastSeenAt
                    && \Illuminate\Support\Carbon::parse($lastSeenAt)->greaterThanOrEqualTo(now()->subSeconds(30));
            })
            ->pluck('data_logger_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function setMonitoringRuntime(DataLogger $dataLogger, bool $enabled): array
    {
        $connectivity = ConnectivityConfig::firstOrCreate(
            ['connectivity_code' => 'REDNODE-' . $dataLogger->logger_code],
            [
                'data_logger_id' => $dataLogger->id,
                'communication_type' => 'RS485',
                'protocol' => 'Modbus RTU',
                'host_or_endpoint' => env('REDNODE_SERIAL_PORT', '/dev/ttyAS2'),
                'gateway_id' => $dataLogger->logger_code,
                'connectivity_status' => 'Offline',
            ]
        );

        $runtime = $connectivity->runtime_state ?? [];
        $connectivity->update([
            'data_logger_id' => $dataLogger->id,
            'connectivity_status' => $enabled ? 'Online' : 'Offline',
            'runtime_state' => array_merge($runtime, [
                'monitoring_enabled' => $enabled,
                'last_action' => $enabled ? 'start' : 'stop',
                'last_commanded_at' => now()->toISOString(),
                'last_error' => $enabled ? null : ($runtime['last_error'] ?? null),
            ]),
        ]);

        return [
            'logger_code' => $dataLogger->logger_code,
            'ok' => true,
            'message' => $enabled
                ? 'Gateway akan start polling saat config berikutnya diambil.'
                : 'Gateway akan stop polling saat config berikutnya diambil.',
            'terminal_log' => [
                $enabled
                    ? 'Runtime logger diset Online dari web.'
                    : 'Runtime logger diset Offline dari web.',
            ],
        ];
    }

    private function liveSensorValue(Sensor $sensor, mixed $value): mixed
    {
        $mapped = $this->canonicalMapping->mappedParameterValue($sensor, $value);

        return $mapped['value_text'] ?? $this->valueWithUnit($value, $sensor->unit);
    }

    private function liveParameterValues(Sensor $sensor, mixed $value): array
    {
        $mapped = $this->canonicalMapping->mappedParameterValue($sensor, $value);

        return $mapped ? [$mapped] : [];
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

    public function storeProject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'project_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        Project::updateOrCreate(['project_code' => $data['project_code']], $data);

        return back()->with('message', 'Project berhasil disimpan.');
    }

    public function storeWorkspace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'hazard' => ['nullable', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'beneficiaries' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data = $this->applyProvinceCoordinates($data);

        GeospatialWorkspace::updateOrCreate(['workspace_code' => $data['workspace_code']], $data);

        return back()->with('message', 'Geospatial workspace berhasil disimpan.');
    }

    public function storeMonitoringStation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'station_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'logger_id' => ['nullable', 'string', 'max:255'],
            'logger_status' => ['required', 'string', 'max:50'],
            'connectivity_status' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data = $this->applyParsedCoordinate($data);

        MonitoringStation::updateOrCreate(['station_code' => $data['station_code']], $data);

        return back()->with('message', 'Monitoring station berhasil disimpan.');
    }

    public function storeWarningStation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'monitoring_station_id' => ['nullable', 'exists:monitoring_stations,id'],
            'station_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'zone_id' => ['nullable', 'string', 'max:255'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'controller_id' => ['nullable', 'string', 'max:255'],
            'controller_model' => ['nullable', 'string', 'max:255'],
            'controller_vendor' => ['nullable', 'string', 'max:255'],
            'controller_status' => ['required', 'string', 'max:50'],
            'output_devices' => ['nullable', 'array'],
            'status' => ['required', 'string', 'max:50'],
            'public_warning_enabled' => ['nullable', 'boolean'],
            'ack_response' => ['nullable', 'string', 'max:255'],
        ]);
        $data['public_warning_enabled'] = $request->boolean('public_warning_enabled');
        $data = $this->applyParsedCoordinate($data);

        WarningStation::updateOrCreate(['station_code' => $data['station_code']], $data);

        return back()->with('message', 'Warning station berhasil disimpan.');
    }

    public function storeSensor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'monitoring_station_id' => ['required', 'exists:monitoring_stations,id'],
            'data_logger_id' => ['nullable', 'exists:data_loggers,id'],
            'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
            'mst_prefix_id' => ['required', 'exists:mst_prefixes,id'],
            'slave_id' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'function_code' => ['required', Rule::in(['FC01', 'FC02', 'FC03', 'FC04'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:125'],
            'poll_interval_ms' => ['required', 'integer', 'min:250', 'max:60000'],
            'sensor_code' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([
                'water_level',
                'rain_gauge',
                'tide_level',
                'seismic_vibration',
                'ground_movement',
                'soil_moisture',
                'river_flow',
                'weather_station',
                'temperature',
                'humidity',
                'pressure',
                'wind_speed',
                'wind_direction',
                'battery_bms',
                'solar_charger',
                'device_health',
            ])],
            'parameter' => ['nullable', 'string', 'max:255'],
            'weather_parameters' => ['nullable', 'array'],
            'weather_parameters.*' => ['string', Rule::in([
                'temperature',
                'humidity',
                'pressure',
                'wind_speed',
                'wind_direction',
                'rainfall',
                'solar_radiation',
                'battery_voltage',
            ])],
            'value' => ['nullable', 'string', 'max:255'],
            'threshold' => ['nullable', 'string', 'max:255'],
            'data_type' => ['required', Rule::in([
                'float32',
                'float64',
                'int8',
                'int16',
                'int32',
                'int64',
                'uint8',
                'uint16',
                'uint32',
                'uint64',
                'boolean',
                'string',
                'ascii',
                'hex',
                'byte',
                'raw',
            ])],
            'scale_factor' => ['required', 'numeric'],
            'offset' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reading_method' => ['required', 'string', 'max:50'],
            'alert_level' => ['required', 'string', 'max:50'],
            'rule' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data['weather_parameters'] = $data['type'] === 'weather_station'
            ? $this->weatherParametersForSensor((int) $data['quantity'], $data['weather_parameters'] ?? [], $data['parameter'] ?? null)
            : null;

        $addressAlreadyUsed = Sensor::query()
            ->where('mst_prefix_id', $data['mst_prefix_id'])
            ->where('slave_id', $data['slave_id'])
            ->where('address', $data['address'])
            ->where('sensor_code', '!=', $data['sensor_code'])
            ->exists();

        if ($addressAlreadyUsed) {
            return back()
                ->withErrors(['address' => 'Kombinasi Prefix Sensors, Slave ID, dan Address sudah dipakai sensor lain.'])
                ->withInput();
        }

        Sensor::updateOrCreate(['sensor_code' => $data['sensor_code']], $data);

        return back()->with('message', 'Sensor berhasil disimpan.');
    }

    public function storeResponsePlan(Request $request): RedirectResponse
    {
        ResponsePlan::create([
            ...$request->validate([
                'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
                'sensor_id' => ['nullable', 'exists:sensors,id'],
                'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
                'notes' => ['nullable', 'string'],
            ]),
            'dashboard_notif' => $request->boolean('dashboard_notif'),
            'sms_blasting' => $request->boolean('sms_blasting'),
            'warning_station_act' => $request->boolean('warning_station_act'),
        ]);

        return back()->with('message', 'Response plan berhasil ditambahkan.');
    }

    private function weatherParametersForSensor(int $quantity, array $selected = [], ?string $hint = null): array
    {
        $configured = collect($selected)
            ->filter()
            ->unique()
            ->values();
        $defaults = collect($this->defaultWeatherParameters($hint));

        return $configured
            ->merge($defaults->reject(fn ($parameter) => $configured->contains($parameter)))
            ->take(max($quantity, $configured->count(), 1))
            ->values()
            ->all();
    }

    private function defaultWeatherParameters(?string $hint = null): array
    {
        $base = [
            'temperature',
            'humidity',
            'pressure',
            'wind_speed',
            'wind_direction',
            'rainfall',
            'solar_radiation',
            'battery_voltage',
        ];
        $hint = Str::lower((string) $hint);

        if (Str::contains($hint, ['angin', 'wind'])) {
            return ['wind_speed', 'wind_direction', ...array_values(array_diff($base, ['wind_speed', 'wind_direction']))];
        }

        if (Str::contains($hint, ['hujan', 'rain'])) {
            return ['rainfall', ...array_values(array_diff($base, ['rainfall']))];
        }

        return $base;
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $models = [
            'project' => Project::class,
            'workspace' => GeospatialWorkspace::class,
            'monitoring' => MonitoringStation::class,
            'warning' => WarningStation::class,
            'sensor' => Sensor::class,
            'response-plan' => ResponsePlan::class,
        ];

        abort_unless(isset($models[$type]), 404);

        if ($type === 'sensor') {
            $this->cleanupSensorReferences($id);
        }

        $models[$type]::findOrFail($id)->delete();

        return back()->with('message', 'Data berhasil dihapus.');
    }

    private function cleanupSensorReferences(int $sensorId): void
    {
        // Hapus ID sensor dari monitored_sensor_ids di semua connectivity configs
        $connectivities = ConnectivityConfig::whereJsonContains('serial_settings->monitored_sensor_ids', $sensorId)->get();

        foreach ($connectivities as $connectivity) {
            $settings = $connectivity->serial_settings ?? [];
            $sensorIds = collect($settings['monitored_sensor_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id !== $sensorId)
                ->values()
                ->all();
            $settings['monitored_sensor_ids'] = $sensorIds;
            $connectivity->update(['serial_settings' => $settings]);
        }

        // Hapus telemetry readings yang terkait sensor ini
        if (Schema::hasTable('telemetry_readings')) {
            TelemetryReading::where('sensor_id', $sensorId)->delete();
        }

        // Hapus response plans yang terkait sensor ini
        if (Schema::hasTable('response_plans')) {
            ResponsePlan::where('sensor_id', $sensorId)->delete();
        }

        // Hapus sensor mapping profiles yang terkait sensor ini
        if (Schema::hasTable('sensor_mapping_profiles')) {
            SensorMappingProfile::where('sensor_id', $sensorId)->delete();
        }
    }

    private function viewData(): array
    {
        $provinces = $this->provinces();

        if (! Schema::hasTable('resq_projects')) {
            return [
                'project' => config('resq_dummy.project'),
                'projects' => collect([]),
                'clusters' => collect(config('resq_dummy.clusters')),
                'monitoringStations' => collect(config('resq_dummy.monitoring_stations')),
                'warningStations' => collect(config('resq_dummy.warning_stations')),
                'sensors' => collect(config('resq_dummy.sensors')),
                'dataLoggers' => collect(config('resq_dummy.data_loggers')),
                'connectivity' => collect(config('resq_dummy.connectivity')),
                'credentials' => collect(config('resq_dummy.credentials')),
                'telemetryReadings' => collect([]),
                'mstPrefixes' => collect([]),
                'responsePlans' => collect([]),
                'provinces' => $provinces,
                'databaseReady' => false,
            ];
        }

        $projectModels = Project::latest()->get();
        $workspaceModels = GeospatialWorkspace::with('project')->latest()->get();
        $monitoringModels = MonitoringStation::with('workspace')->latest()->get();
        $warningModels = WarningStation::with(['workspace', 'monitoringStation'])->latest()->get();
        $sensorQuery = Sensor::with(['workspace', 'monitoringStation', 'dataLogger', 'warningStation', 'mstPrefix']);
        if (Schema::hasTable('sensor_mapping_profiles')) {
            $sensorQuery->with('mappingProfile');
        }
        $sensorModels = $sensorQuery->latest()->get();
        $mstPrefixes = Schema::hasTable('mst_prefixes') ? MstPrefix::latest()->get() : collect();
        $dataLoggerModels = Schema::hasTable('data_loggers')
            ? DataLogger::with('monitoringStation')->latest()->get()
            : collect();
        $connectivityModels = Schema::hasTable('connectivity_configs')
            ? ConnectivityConfig::with('dataLogger')->latest()->get()
            : collect();
        $credentialModels = Schema::hasTable('device_credentials')
            ? DeviceCredential::with('dataLogger')->latest()->get()
            : collect();
        $telemetryModels = Schema::hasTable('telemetry_readings')
            ? TelemetryReading::with(['sensor.monitoringStation', 'dataLogger'])->latest('received_at')->latest()->limit(100)->get()
            : collect();

        $projects = $projectModels->map(fn (Project $project) => [
            'db_id' => $project->id,
            'id' => $project->project_code,
            'name' => $project->name,
            'owner' => $project->owner,
            'date' => $project->project_date,
            'status' => $project->status,
        ]);

        $workspaces = $workspaceModels->map(fn (GeospatialWorkspace $workspace) => [
            'db_id' => $workspace->id,
            'project_db_id' => $workspace->project_id,
            'id' => $workspace->workspace_code,
            'project_id' => $workspace->project?->project_code,
            'name' => $workspace->name,
            'hazard' => $workspace->hazard,
            'province' => $workspace->province,
            'city' => $workspace->city,
            'beneficiaries' => $workspace->beneficiaries,
            'latitude' => $workspace->latitude,
            'longitude' => $workspace->longitude,
            'status' => $workspace->status,
            'monitoring_station_id' => optional($monitoringModels->firstWhere('workspace_id', $workspace->id))->station_code,
            'warning_station_id' => optional($warningModels->firstWhere('workspace_id', $workspace->id))->station_code,
        ]);

        $monitoringStations = $monitoringModels->map(fn (MonitoringStation $station) => [
            'db_id' => $station->id,
            'workspace_db_id' => $station->workspace_id,
            'id' => $station->station_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'name' => $station->name,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'logger_id' => $station->logger_id,
            'logger_status' => $station->logger_status,
            'connectivity_status' => $station->connectivity_status,
            'warning_station_id' => optional($warningModels->firstWhere('monitoring_station_id', $station->id))->station_code,
            'status' => $station->status,
        ]);

        $warningStations = $warningModels->map(fn (WarningStation $station) => [
            'db_id' => $station->id,
            'workspace_db_id' => $station->workspace_id,
            'monitoring_station_db_id' => $station->monitoring_station_id,
            'id' => $station->station_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'source_monitoring_station_id' => $station->monitoringStation?->station_code,
            'name' => $station->name,
            'zone_id' => $station->zone_id,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'controller_id' => $station->controller_id,
            'controller_model' => $station->controller_model,
            'controller_vendor' => $station->controller_vendor,
            'controller_status' => $station->controller_status,
            'output_devices' => $station->output_devices ?? [],
            'status' => $station->status,
            'public_warning_enabled' => $station->public_warning_enabled,
            'ack_response' => $station->ack_response,
        ]);

        $sensors = $sensorModels->map(fn (Sensor $sensor) => [
            'db_id' => $sensor->id,
            'workspace_db_id' => $sensor->workspace_id,
            'monitoring_station_db_id' => $sensor->monitoring_station_id,
            'data_logger_db_id' => $sensor->data_logger_id,
            'warning_station_db_id' => $sensor->warning_station_id,
            'mst_prefix_db_id' => $sensor->mst_prefix_id,
            'id' => $sensor->sensor_code,
            'cluster_id' => $sensor->workspace?->workspace_code,
            'monitoring_station_id' => $sensor->monitoringStation?->station_code,
            'data_logger_id' => $sensor->dataLogger?->logger_code,
            'warning_station_id' => $sensor->warningStation?->station_code,
            'mst_prefix' => $sensor->mstPrefix?->prefix_code,
            'slave_id' => $sensor->slave_id,
            'address' => $sensor->address,
            'function_code' => $sensor->function_code ?? 'FC03',
            'quantity' => $sensor->quantity ?? 1,
            'poll_interval_ms' => $sensor->poll_interval_ms ?? 1000,
            'type' => $sensor->type,
            'parameter' => $sensor->parameter,
            'weather_parameters' => $sensor->weather_parameters ?? [],
            'value' => $sensor->value,
            'threshold' => $sensor->threshold,
            'data_type' => $sensor->data_type,
            'scale_factor' => $sensor->scale_factor,
            'offset' => $sensor->offset,
            'unit' => $sensor->unit,
            'reading_method' => $sensor->reading_method,
            'alert_level' => $sensor->alert_level,
            'rule' => $sensor->rule,
            'status' => $sensor->status,
            'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
            'is_canonical_mapped' => $sensor->relationLoaded('mappingProfile') && $sensor->mappingProfile !== null,
        ]);

        return [
            'project' => $projects->first() ?? config('resq_dummy.project'),
            'projects' => $projects,
            'clusters' => $workspaces,
            'monitoringStations' => $monitoringStations,
            'warningStations' => $warningStations,
            'sensors' => $sensors,
            'dataLoggers' => $this->dataLoggersFromModels($dataLoggerModels),
            'connectivity' => $this->connectivityFromModels($connectivityModels),
            'credentials' => $this->credentialsFromModels($credentialModels),
            'telemetryReadings' => $this->telemetryFromModels($telemetryModels),
            'mstPrefixes' => $mstPrefixes,
            'responsePlans' => ResponsePlan::latest()->get(),
            'provinces' => $provinces,
            'canonicalParameters' => Schema::hasTable('canonical_parameters') ? CanonicalParameter::orderBy('domain')->orderBy('field_identity')->get() : collect(),
            'sensorMappingProfiles' => Schema::hasTable('sensor_mapping_profiles') ? SensorMappingProfile::with(['sensor', 'canonicalParameter'])->latest()->get() : collect(),
            'databaseReady' => true,
        ];
    }

    private function provinces(): array
    {
        if (Schema::hasTable('provinces')) {
            return Province::query()
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        return config('indonesia.provinces') ?? [];
    }

    private function dataLoggersFromModels($dataLoggers)
    {
        return collect($dataLoggers)->map(fn (DataLogger $logger) => [
            'db_id' => $logger->id,
            'id' => $logger->logger_code,
            'monitoring_station_db_id' => $logger->monitoring_station_id,
            'monitoring_station_id' => $logger->monitoringStation?->station_code,
            'serial_number' => $logger->serial_number,
            'logger_model' => $logger->logger_model,
            'vendor' => $logger->vendor,
            'firmware_version' => $logger->firmware_version,
            'device_label' => $logger->device_label,
            'logger_status' => $logger->logger_status,
        ]);
    }

    private function connectivityFromModels($connectivity)
    {
        return collect($connectivity)->map(fn (ConnectivityConfig $item) => [
            'db_id' => $item->id,
            'data_logger_db_id' => $item->data_logger_id,
            'id' => $item->connectivity_code,
            'logger_id' => $item->dataLogger?->logger_code,
            'communication_type' => $item->communication_type,
            'protocol' => $item->protocol,
            'host_or_endpoint' => $item->host_or_endpoint,
            'port' => $item->port,
            'topic_or_api_path' => $item->topic_or_api_path,
            'gateway_id' => $item->gateway_id,
            'sim_number' => $item->sim_number,
            'imei' => $item->imei,
            'apn' => $item->apn,
            'connectivity_status' => $item->connectivity_status,
        ]);
    }

    private function credentialsFromModels($credentials)
    {
        return collect($credentials)->map(fn (DeviceCredential $credential) => [
            'db_id' => $credential->id,
            'data_logger_db_id' => $credential->data_logger_id,
            'id' => $credential->credential_code,
            'logger_id' => $credential->dataLogger?->logger_code,
            'device_token' => $credential->device_token,
            'mqtt_username' => $credential->mqtt_username,
            'mqtt_password_hash' => $credential->mqtt_password_hash,
            'certificate_ref' => $credential->certificate_ref,
            'credential_status' => $credential->credential_status,
            'created_at' => optional($credential->created_at)->format('Y-m-d H:i:s'),
            'revoked_at' => optional($credential->revoked_at)->format('Y-m-d H:i:s'),
        ]);
    }

    private function telemetryFromModels($readings)
    {
        return collect($readings)->map(fn (TelemetryReading $reading) => [
            'db_id' => $reading->id,
            'sensor_db_id' => $reading->sensor_id,
            'sensor_id' => $reading->sensor?->sensor_code,
            'monitoring_station_id' => $reading->sensor?->monitoringStation?->station_code,
            'data_logger_db_id' => $reading->data_logger_id,
            'data_logger_id' => $reading->dataLogger?->logger_code,
            'value' => $reading->value,
            'alert_level' => $reading->alert_level,
            'status' => $reading->status,
            'received_at' => optional($reading->received_at ?? $reading->created_at)->diffForHumans(),
            'received_at_input' => optional($reading->received_at)->format('Y-m-d\TH:i'),
        ]);
    }

    private function applyProvinceCoordinates(array $data): array
    {
        if ((! empty($data['latitude']) && ! empty($data['longitude'])) || ! Schema::hasTable('provinces')) {
            return $data;
        }

        $province = Province::where('name', $data['province'])->first();

        if ($province) {
            $data['latitude'] = $province->latitude;
            $data['longitude'] = $province->longitude;
        }

        return $data;
    }

    private function applyParsedCoordinate(array $data): array
    {
        if ((! empty($data['latitude']) && ! empty($data['longitude'])) || empty($data['coordinate'])) {
            return $data;
        }

        $parts = array_map('trim', explode(',', $data['coordinate']));

        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $data['latitude'] = $parts[0];
            $data['longitude'] = $parts[1];
        }

        return $data;
    }
}
