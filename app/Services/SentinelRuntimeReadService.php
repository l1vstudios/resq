<?php

namespace App\Services;

use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\HydrometEwsRelationship;
use App\Models\HydrometHazardClassification;
use App\Models\HydrometWdamConfig;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SentinelRuntimeReadService
{
    private const HAZARD_RANK = [
        'NORMAL' => 0,
        'WASPADA' => 1,
        'SIAGA' => 2,
        'AWAS' => 3,
        'DANGER' => 3,
        'CRITICAL' => 3,
    ];

    private const INTEGRITY_RANK = [
        'Healthy' => 0,
        'Warning' => 1,
        'Critical' => 2,
        'Offline' => 3,
    ];

    public function latestReadings(MonitoringStation $station, int $freshSeconds = 300): array
    {
        $station->loadMissing(['project', 'workspace.project', 'sensors.dataLogger', 'sensors.mappingProfile.canonicalParameter', 'sensors.mappingProfiles.canonicalParameter']);
        $sensors = $station->sensors;
        $readings = $this->latestReadingsForSensors($sensors);
        $freshAfter = now()->subSeconds($freshSeconds);

        return [
            'station' => $this->stationIdentity($station),
            'fresh_after' => $freshAfter->toISOString(),
            'generated_at' => now()->toISOString(),
            'readings' => $sensors
                ->flatMap(fn (Sensor $sensor) => $this->parameterReadingRows($sensor, $readings->get($sensor->id), $freshAfter))
                ->values()
                ->all(),
        ];
    }

    public function timeSeries(MonitoringStation $station, array $filters): array
    {
        $station->loadMissing(['project', 'workspace.project', 'sensors.dataLogger']);
        $station->loadMissing(['sensors.mappingProfiles.canonicalParameter']);
        $sensorIds = $station->sensors->pluck('id');
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from']) : now()->subDay();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to']) : now();
        $limit = min(max((int) ($filters['limit'] ?? 500), 1), 2000);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $query = TelemetryReading::with(['sensor.mappingProfile.canonicalParameter'])
            ->whereIn('sensor_id', $sensorIds)
            ->whereBetween('received_at', [$from, $to])
            ->orderBy('received_at')
            ->orderBy('id');

        if (! empty($filters['sensor_id'])) {
            $sensorId = (int) $filters['sensor_id'];
            abort_unless($sensorIds->contains($sensorId), 403);
            $query->where('sensor_id', $sensorId);
        }

        if (! empty($filters['sensor_code'])) {
            $sensor = $station->sensors->firstWhere('sensor_code', $filters['sensor_code']);
            abort_unless($sensor, 403);
            $query->where('sensor_id', $sensor->id);
        }

        $rows = $query->limit($limit)->get();

        return [
            'station' => $this->stationIdentity($station),
            'from' => $from->toISOString(),
            'to' => $to->toISOString(),
            'limit' => $limit,
            'count' => $rows->count(),
            'readings' => $rows->map(fn (TelemetryReading $reading) => $this->timeSeriesRow($reading))->values()->all(),
        ];
    }

    public function stationRuntime(MonitoringStation $station, int $freshSeconds = 300): array
    {
        $station->loadMissing([
            'project',
            'workspace.project',
            'corridor',
            'sensors.dataLogger',
            'sensors.mappingProfile.canonicalParameter',
            'sensors.mappingProfiles.canonicalParameter',
            'dataLoggers.connectivityConfigs',
            'hydrometEwsRelationships.hazardClassifications.sensor',
            'hydrometEwsRelationships.hazardClassifications.canonicalParameter',
            'hydrometEwsRelationships.wdamConfigs.warningStation.devices',
        ]);

        $latest = $this->latestReadingsForSensors($station->sensors);
        $freshAfter = now()->subSeconds($freshSeconds);
        $integrity = $this->integrity($station, $latest, $freshAfter);

        return [
            'station' => $this->stationIdentity($station),
            'latest' => $this->latestReadings($station, $freshSeconds)['readings'],
            'integrity' => $integrity,
            'administrative' => $this->administrativeState($station),
            'hazard' => $this->stationHazard($station, $latest),
            'warning_state' => $this->stationWarningState($station),
            'analytical_outputs' => $this->analyticalOutputs($station),
            'unresolved_runtime_rules' => $this->unresolvedRuntimeRules(),
        ];
    }

    public function projectRuntime(Project $project, int $freshSeconds = 300): array
    {
        $project->loadMissing([
            'monitoringStations.workspace.project',
            'monitoringStations.corridor',
            'monitoringStations.sensors.dataLogger',
            'monitoringStations.sensors.mappingProfile.canonicalParameter',
            'monitoringStations.sensors.mappingProfiles.canonicalParameter',
            'monitoringStations.dataLoggers.connectivityConfigs',
            'monitoringStations.hydrometEwsRelationships.hazardClassifications.sensor',
            'monitoringStations.hydrometEwsRelationships.hazardClassifications.canonicalParameter',
            'monitoringStations.hydrometEwsRelationships.wdamConfigs.warningStation.devices',
            'warningStations.devices',
            'corridors',
        ]);

        $sensorIds = $project->monitoringStations
            ->flatMap(fn (MonitoringStation $station) => $station->sensors->pluck('id'));
        $latest = $this->latestReadingsForSensorIds($sensorIds);
        $freshAfter = now()->subSeconds($freshSeconds);
        $stationRows = $project->monitoringStations
            ->map(fn (MonitoringStation $station) => [
                'station' => $this->stationIdentity($station),
                'integrity' => $this->integrity($station, $latest, $freshAfter),
                'administrative' => $this->administrativeState($station),
                'hazard' => $this->stationHazard($station, $latest),
                'warning_state' => $this->stationWarningState($station),
            ])
            ->values();

        return [
            'project' => [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'name' => $project->name,
                'status' => $project->status,
            ],
            'generated_at' => now()->toISOString(),
            'fresh_after' => $freshAfter->toISOString(),
            'stations' => $stationRows->all(),
            'corridors' => $project->corridors
                ->map(fn (CorridorMonitoring $corridor) => $this->corridorHazard($corridor, $stationRows))
                ->values()
                ->all(),
            'warning_stations' => $project->warningStations
                ->map(fn (WarningStation $station) => $this->warningStationStatus($station))
                ->values()
                ->all(),
            'unresolved_runtime_rules' => $this->unresolvedRuntimeRules(),
        ];
    }

    public function latestReadingsForSensors(Collection $sensors): Collection
    {
        return $this->latestReadingsForSensorIds($sensors->pluck('id'));
    }

    public function latestReadingsForSensorIds(Collection $sensorIds): Collection
    {
        if ($sensorIds->isEmpty()) {
            return collect();
        }

        $sensorIds = $sensorIds->unique()->values();
        $latestPerSensor = TelemetryReading::query()
            ->select('sensor_id')
            ->selectRaw('MAX(received_at) as latest_received_at')
            ->whereIn('sensor_id', $sensorIds)
            ->groupBy('sensor_id');

        return TelemetryReading::with(['sensor.mappingProfile.canonicalParameter', 'dataLogger'])
            ->joinSub($latestPerSensor, 'latest_per_sensor', function ($join) {
                $join->on('telemetry_readings.sensor_id', '=', 'latest_per_sensor.sensor_id')
                    ->on('telemetry_readings.received_at', '=', 'latest_per_sensor.latest_received_at');
            })
            ->select('telemetry_readings.*')
            ->orderByDesc('telemetry_readings.received_at')
            ->orderByDesc('telemetry_readings.id')
            ->get()
            ->unique('sensor_id')
            ->keyBy('sensor_id');
    }

    public function stationIdentity(MonitoringStation $station): array
    {
        return [
            'id' => $station->id,
            'station_code' => $station->station_code,
            'name' => $station->name,
            'project_id' => $station->project_id ?: $station->workspace?->project_id,
            'project_code' => $station->project?->project_code ?? $station->workspace?->project?->project_code,
            'workspace_id' => $station->workspace_id,
            'corridor_id' => $station->corridor_id,
            'corridor_code' => $station->corridor?->corridor_code,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude !== null ? (float) $station->latitude : null,
            'longitude' => $station->longitude !== null ? (float) $station->longitude : null,
        ];
    }

    public function administrativeState(MonitoringStation|WarningStation $station): array
    {
        return [
            'registration_status' => $station->registration_status ?? 'unregistered',
            'service_status' => $station->service_status ?? $station->status ?? 'not_configured',
            'service_period' => [
                'start' => optional($station->service_period_start)->toDateString(),
                'end' => optional($station->service_period_end)->toDateString(),
            ],
            'entitlement' => $station->entitlement ?? 'not_configured',
            'package_status' => $station->package_status ?? 'not_configured',
            'administrative_attention' => $station->administrative_attention,
        ];
    }

    public function unresolvedRuntimeRules(): array
    {
        return [
            'Telemetry freshness threshold is configurable; no domain SLA threshold is defined.',
            'Power-health thresholds are not defined in the current station/device domain.',
            'Calibration freshness/expiry rules are not represented yet.',
            'Maintenance due/overdue rules are not represented yet.',
            'Overall integrity is a read-model severity rollup, not a certified operational formula.',
        ];
    }

    public static function unresolvedAnalyticalRules(string $function): array
    {
        return match ($function) {
            'TDE' => [
                'TDE rainfall/event definition is not defined.',
                'Temporal aggregation and event separation rules are not defined.',
            ],
            'Discharge' => [
                'Stage-discharge rating curve or velocity-area method is not defined.',
                'Cross-section geometry and coefficient rules are not defined.',
            ],
            'CFPE' => [
                'Longitudinal flood propagation model is not defined.',
                'Required routing parameters and boundary conditions are not defined.',
            ],
            default => ['Analytical calculation rules are not defined.'],
        };
    }

    private function parameterReadingRows(Sensor $sensor, ?TelemetryReading $reading, CarbonInterface $freshAfter): array
    {
        $receivedAt = $reading?->received_at ?? $sensor->last_seen_at;
        $fresh = $receivedAt && $receivedAt->gte($freshAfter);
        $parameterValues = $this->normalizedParameterValues($sensor, $reading);

        if ($parameterValues->isNotEmpty()) {
            return $parameterValues
                ->map(fn (array $item) => $this->readingRow($sensor, $reading, $fresh, $receivedAt, [
                    'parameter' => $item['parameter'] ?? $item['field'] ?? $sensor->parameter ?? $sensor->type,
                    'label' => $item['label'] ?? null,
                    'value' => $item['value'] ?? $item['numeric_value'] ?? $item['value_text'] ?? null,
                    'unit' => $item['unit'] ?? $sensor->unit,
                    'value_text' => $item['value_text'] ?? null,
                    'raw' => $item['raw'] ?? null,
                    'source_parameter' => $item['source_parameter'] ?? null,
                    'register_address' => $item['register_address'] ?? null,
                    'register_index' => $item['register_index'] ?? null,
                    'registers' => $item['registers'] ?? null,
                    'value_type' => $item['value_type'] ?? null,
                    'data_length' => $item['data_length'] ?? null,
                    'byte_order' => $item['byte_order'] ?? null,
                    'scale_factor' => $item['scale_factor'] ?? null,
                    'offset' => $item['offset'] ?? null,
                    'modbus_frame' => $item['modbus_frame'] ?? null,
                ]))
                ->all();
        }

        return [
            $this->readingRow($sensor, $reading, $fresh, $receivedAt, [
                'parameter' => $sensor->mappingProfile?->canonicalParameter?->field_identity ?? $sensor->parameter ?? $sensor->type,
                'value' => $reading?->numeric_value ?? $reading?->value ?? $sensor->value,
                'unit' => $sensor->unit,
            ]),
        ];
    }

    private function readingRow(
        Sensor $sensor,
        ?TelemetryReading $reading,
        bool $fresh,
        mixed $receivedAt,
        array $value
    ): array {
        return [
            'sensor_id' => $sensor->id,
            'sensor_code' => $sensor->sensor_code,
            'data_logger_id' => $reading?->data_logger_id ?? $sensor->data_logger_id,
            'data_logger_code' => $reading?->dataLogger?->logger_code ?? $sensor->dataLogger?->logger_code,
            'parameter' => $value['parameter'],
            'label' => $value['label'] ?? $this->parameterLabel((string) $value['parameter']),
            'timestamp' => optional($receivedAt)->toISOString(),
            'value' => $value['value'],
            'unit' => $value['unit'],
            'value_text' => $value['value_text'] ?? $this->valueWithUnit($this->formatDecodedValue($value['value']), $value['unit'] ?? null),
            'raw' => $value['raw'] ?? $reading?->raw_value,
            'register_address' => $value['register_address'] ?? null,
            'register_index' => $value['register_index'] ?? null,
            'registers' => $value['registers'] ?? ($reading?->registers ?? null),
            'source_parameter' => $value['source_parameter'] ?? null,
            'value_type' => $value['value_type'] ?? null,
            'data_length' => $value['data_length'] ?? null,
            'byte_order' => $value['byte_order'] ?? null,
            'scale_factor' => $value['scale_factor'] ?? null,
            'offset' => $value['offset'] ?? null,
            'modbus_frame' => $value['modbus_frame'] ?? null,
            'fresh' => $fresh,
            'data_freshness' => $fresh ? 'fresh' : 'stale',
            'status' => $reading?->status ?? $sensor->status,
            'alert_level' => $reading?->alert_level ?? $sensor->alert_level,
        ];
    }

    private function timeSeriesRow(TelemetryReading $reading): array
    {
        $parameterValues = $this->normalizedParameterValues($reading->sensor, $reading);

        return [
            'telemetry_id' => $reading->id,
            'sensor_id' => $reading->sensor_id,
            'sensor_code' => $reading->sensor?->sensor_code,
            'parameter' => $reading->sensor?->mappingProfile?->canonicalParameter?->field_identity
                ?? $reading->sensor?->parameter
                ?? $reading->sensor?->type,
            'timestamp' => optional($reading->received_at)->toISOString(),
            'value' => $reading->numeric_value ?? $reading->value,
            'raw_value' => $reading->raw_value ?? $reading->value,
            'unit' => $reading->sensor?->unit,
            'parameter_values' => $parameterValues->values()->all(),
            'status' => $reading->status,
            'alert_level' => $reading->alert_level,
        ];
    }

    private function normalizedParameterValues(?Sensor $sensor, ?TelemetryReading $reading): Collection
    {
        $existing = collect($reading?->parameter_values ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => $this->normalizeParameterValueItem($item))
            ->values();

        if ($existing->isNotEmpty() || ! $sensor || ! $reading) {
            return $this->deduplicateParameterValues($this->enrichExistingParameterValues($existing, $sensor, $reading));
        }

        $decoded = $this->parameterValuesFromRegisters($sensor, $reading->registers ?? []);
        if ($decoded->isNotEmpty()) {
            return $decoded;
        }

        return $this->parameterValuesFromDisplayText($sensor, (string) ($reading->value ?? ''));
    }

    private function enrichExistingParameterValues(Collection $existing, ?Sensor $sensor, ?TelemetryReading $reading): Collection
    {
        if (! $sensor || ! $reading || empty($reading->registers)) {
            return $existing;
        }

        $decoded = $this->parameterValuesFromRegisters($sensor, $reading->registers)
            ->keyBy(fn (array $item) => $this->parameterValueKey($item));

        if ($decoded->isEmpty()) {
            return $existing;
        }

        return $existing->map(function (array $item) use ($decoded) {
            $decodedItem = $decoded->get($this->parameterValueKey($item));

            return $decodedItem ? array_merge($decodedItem, $item) : $item;
        });
    }

    private function normalizeParameterValueItem(array $item): array
    {
        $parameter = $item['parameter'] ?? $item['canonical_field'] ?? $item['field'] ?? $item['source_parameter'] ?? 'Parameter';
        $unit = trim((string) ($item['unit'] ?? $item['canonical_unit'] ?? $item['source_unit'] ?? ''));
        $value = $item['value'] ?? $item['numeric_value'] ?? $item['raw'] ?? $item['value_text'] ?? null;

        return [
            ...$item,
            'parameter' => $parameter,
            'label' => $item['label'] ?? $this->parameterLabel((string) $parameter),
            'value' => $value,
            'unit' => $unit,
            'value_text' => $item['value_text'] ?? $this->valueWithUnit($this->formatDecodedValue($value), $unit),
        ];
    }

    private function parameterValuesFromRegisters(Sensor $sensor, mixed $registers): Collection
    {
        if (! is_array($registers) || $registers === []) {
            return collect();
        }

        $registers = collect($registers)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        if ($registers === []) {
            return collect();
        }

        $profiles = $sensor->relationLoaded('mappingProfiles')
            ? $sensor->mappingProfiles
            : $sensor->mappingProfiles()->with('canonicalParameter')->get();

        $profiles = $profiles
            ->filter(fn ($profile) => $profile->status === 'active' && $profile->canonicalParameter !== null)
            ->sortBy(fn ($profile) => $this->integerAddress($profile->register_address) ?? PHP_INT_MAX)
            ->values();

        if ($profiles->isEmpty()) {
            return collect();
        }

        $baseAddress = $this->integerAddress($sensor->address);
        if ($baseAddress === null) {
            $baseAddress = $profiles
                ->map(fn ($profile) => $this->integerAddress($profile->register_address))
                ->filter(fn ($address) => $address !== null)
                ->min();
        }

        $frame = $this->modbusFrameAudit($sensor, $registers);

        return $this->deduplicateParameterValues($profiles
            ->map(function ($profile) use ($registers, $baseAddress, $frame) {
                $address = $this->integerAddress($profile->register_address) ?? 0;
                $registerIndex = max($address - (int) ($baseAddress ?? 0), 0);
                $dataLength = max((int) ($profile->data_length ?? 1), 1);
                $slice = array_slice($registers, $registerIndex, $dataLength);

                if ($slice === []) {
                    return null;
                }

                $raw = $this->modbusValueFromRegisters($slice, $profile->value_type, $profile->byte_order);
                $scale = is_numeric($profile->scale_factor) ? (float) $profile->scale_factor : 1.0;
                $offset = is_numeric($profile->offset) ? (float) $profile->offset : 0.0;
                $value = ((float) $raw * $scale) + $offset;
                $parameter = $profile->canonicalParameter;
                $unit = trim((string) ($parameter->canonical_unit ?: $profile->source_unit));

                return [
                    'parameter' => $parameter->field_identity,
                    'label' => $this->parameterLabel($parameter->field_identity),
                    'source_parameter' => $profile->source_parameter,
                    'register_address' => $profile->register_address,
                    'register_index' => $registerIndex,
                    'registers' => $slice,
                    'raw' => $raw,
                    'value' => $value,
                    'unit' => $unit,
                    'value_type' => $profile->value_type,
                    'data_length' => $dataLength,
                    'byte_order' => $profile->byte_order,
                    'scale_factor' => $scale,
                    'offset' => $offset,
                    'modbus_frame' => $frame,
                    'requirements' => $parameter->input_requirements ?? null,
                    'datasheet_source_url' => $parameter->input_requirements['source_url'] ?? null,
                    'datasheet_reference' => $parameter->input_requirements['source_reference'] ?? null,
                    'value_text' => $this->valueWithUnit($this->formatDecodedValue($value), $unit),
                ];
            })
            ->filter()
            ->values());
    }

    private function parameterValuesFromDisplayText(Sensor $sensor, string $text): Collection
    {
        if (trim($text) === '') {
            return collect();
        }

        $profiles = $sensor->relationLoaded('mappingProfiles')
            ? $sensor->mappingProfiles
            : $sensor->mappingProfiles()->with('canonicalParameter')->get();

        return $this->deduplicateParameterValues(collect(explode(',', $text))
            ->map(function (string $segment) use ($profiles) {
                $segment = trim($segment);
                if (! preg_match('/(-?\d+(?:[.,]\d+)?(?:e[+-]?\d+)?)/i', $segment, $match, PREG_OFFSET_CAPTURE)) {
                    return null;
                }

                $number = str_replace(',', '.', $match[1][0]);
                $offset = $match[1][1];
                $name = trim(substr($segment, 0, $offset));
                if ($name === '') {
                    return null;
                }
                $unit = trim(substr($segment, $offset + strlen($match[1][0])));
                $profile = $profiles->first(fn ($profile) => $this->sameParameterName($name, $profile));
                $parameter = $profile?->canonicalParameter?->field_identity ?? $name;

                return [
                    'parameter' => $parameter,
                    'label' => $this->parameterLabel((string) $parameter),
                    'source_parameter' => $profile?->source_parameter ?? $name,
                    'register_address' => $profile?->register_address,
                    'register_index' => $profile ? $this->integerAddress($profile->register_address) : null,
                    'raw' => $number,
                    'value' => is_numeric($number) ? (float) $number : $number,
                    'unit' => $unit,
                    'value_text' => trim($number . ($unit !== '' ? ' ' . $unit : '')),
                ];
            })
            ->filter()
            ->values());
    }

    private function deduplicateParameterValues(Collection $values): Collection
    {
        return $values
            ->reverse()
            ->unique(fn (array $item) => $this->parameterValueKey($item))
            ->reverse()
            ->values();
    }

    private function parameterValueKey(array $item): string
    {
        $parameter = $item['parameter'] ?? $item['canonical_field'] ?? $item['field'] ?? $item['source_parameter'] ?? 'parameter';
        $register = $item['register_address'] ?? '';

        return $this->normalizeParameterName((string) $parameter) . '|' . (string) $register;
    }

    private function sameParameterName(string $name, mixed $profile): bool
    {
        $normalized = $this->normalizeParameterName($name);

        return collect([
            $profile->source_parameter ?? null,
            $profile->canonicalParameter?->field_identity,
            $this->parameterLabel((string) $profile->canonicalParameter?->field_identity),
        ])
            ->filter()
            ->contains(fn ($candidate) => $this->normalizeParameterName((string) $candidate) === $normalized);
    }

    private function normalizeParameterName(string $name): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]+/i', '', $name) ?: $name);
    }

    private function parameterLabel(string $parameter): string
    {
        return trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', str_replace('_', ' ', $parameter)));
    }

    private function integerAddress(mixed $address): ?int
    {
        if ($address === null || $address === '' || ! is_numeric($address)) {
            return null;
        }

        $value = (int) $address;

        if ($value >= 40001 && $value <= 49999) {
            return $value - 40001;
        }

        if ($value >= 30001 && $value <= 39999) {
            return $value - 30001;
        }

        return $value;
    }

    private function modbusValueFromRegisters(array $registers, ?string $dataType, ?string $byteOrder): float|int
    {
        $dataType = Str::lower((string) ($dataType ?: 'uint16'));

        if (Str::contains($dataType, 'float') && count($registers) >= 2) {
            return unpack('G', $this->modbusBytes($registers, $byteOrder))[1];
        }

        if (Str::contains($dataType, 'int32') && count($registers) >= 2) {
            $value = unpack('N', $this->modbusBytes($registers, $byteOrder))[1];

            return $value > 0x7fffffff ? $value - 0x100000000 : $value;
        }

        if (Str::contains($dataType, 'uint32') && count($registers) >= 2) {
            return unpack('N', $this->modbusBytes($registers, $byteOrder))[1];
        }

        $raw = ((int) ($registers[0] ?? 0)) & 0xffff;

        return Str::contains($dataType, 'int16') && $raw > 0x7fff ? $raw - 0x10000 : $raw;
    }

    private function modbusFrameAudit(Sensor $sensor, array $registers): array
    {
        $slaveId = ((int) ($sensor->slave_id ?: 1)) & 0xff;
        $functionCode = $this->functionCodeNumber($sensor->function_code);
        $address = (int) ($this->integerAddress($sensor->address) ?? 0);
        $quantity = max((int) ($sensor->quantity ?: count($registers) ?: 1), count($registers) ?: 1);
        $request = $this->appendModbusCrc([
            $slaveId,
            $functionCode,
            ($address >> 8) & 0xff,
            $address & 0xff,
            ($quantity >> 8) & 0xff,
            $quantity & 0xff,
        ]);
        $registerBytes = [];

        foreach ($registers as $register) {
            $word = ((int) $register) & 0xffff;
            $registerBytes[] = ($word >> 8) & 0xff;
            $registerBytes[] = $word & 0xff;
        }

        $response = $this->appendModbusCrc([
            $slaveId,
            $functionCode,
            count($registerBytes) & 0xff,
            ...$registerBytes,
        ]);

        return [
            'slave_id' => $slaveId,
            'function_code' => 'FC' . str_pad((string) $functionCode, 2, '0', STR_PAD_LEFT),
            'address' => $address,
            'quantity' => $quantity,
            'tx' => $this->hexFrame($request),
            'rx' => $this->hexFrame($response),
            'register_words' => $registers,
            'register_bytes' => $this->hexFrame($registerBytes),
            'note' => 'Reconstructed from stored Modbus registers.',
        ];
    }

    private function functionCodeNumber(mixed $functionCode): int
    {
        $digits = preg_replace('/[^0-9]/', '', (string) ($functionCode ?: '03'));

        return max((int) $digits, 1);
    }

    private function appendModbusCrc(array $bytes): array
    {
        $crc = 0xffff;

        foreach ($bytes as $byte) {
            $crc ^= ((int) $byte) & 0xff;

            for ($index = 0; $index < 8; $index++) {
                $crc = ($crc & 1) ? (($crc >> 1) ^ 0xa001) : ($crc >> 1);
            }
        }

        $crc &= 0xffff;

        return [...$bytes, $crc & 0xff, ($crc >> 8) & 0xff];
    }

    private function hexFrame(array $bytes): string
    {
        return collect($bytes)
            ->map(fn ($byte) => str_pad(strtoupper(dechex(((int) $byte) & 0xff)), 2, '0', STR_PAD_LEFT))
            ->implode(' ');
    }

    private function modbusBytes(array $registers, ?string $byteOrder): string
    {
        $words = [
            ((int) ($registers[0] ?? 0)) & 0xffff,
            ((int) ($registers[1] ?? 0)) & 0xffff,
        ];
        $byteOrder = Str::upper((string) ($byteOrder ?: 'ABCD'));

        return match ($byteOrder) {
            'CDAB' => pack('n2', $words[1], $words[0]),
            'BADC' => pack('v2', $words[0], $words[1]),
            'DCBA' => pack('v2', $words[1], $words[0]),
            default => pack('n2', $words[0], $words[1]),
        };
    }

    private function formatDecodedValue(mixed $value): string
    {
        if (is_numeric($value) && is_finite((float) $value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return (string) ($value ?? '-');
    }

    private function valueWithUnit(mixed $value, ?string $unit): string
    {
        $text = trim((string) ($value ?? '-'));
        $unit = trim((string) $unit);

        if ($text === '' || $text === '-' || $unit === '' || $unit === '0') {
            return $text === '' ? '-' : $text;
        }

        return Str::endsWith(Str::lower($text), Str::lower($unit))
            ? $text
            : trim($text . ' ' . $unit);
    }

    private function integrity(MonitoringStation $station, Collection $latest, CarbonInterface $freshAfter): array
    {
        $connectivityConfigs = $station->dataLoggers->flatMap->connectivityConfigs;
        $connectivity = $this->connectivityIntegrity($station, $connectivityConfigs, $freshAfter);
        $telemetry = $this->telemetryIntegrity($station, $latest, $freshAfter);
        $device = $this->deviceIntegrity($station, $latest, $freshAfter);
        $components = [
            'connectivity' => $connectivity,
            'telemetry_data_health' => $telemetry,
            'power_health' => ['status' => 'Warning', 'basis' => 'Power thresholds are not configured.'],
            'device_instrument_health' => $device,
            'calibration' => ['status' => 'Warning', 'basis' => 'Calibration metadata is not configured.'],
            'maintenance' => ['status' => 'Warning', 'basis' => 'Maintenance schedule is not configured.'],
        ];

        return [
            'overall' => $this->worstIntegrity(collect($components)->pluck('status')),
            'last_seen_at' => optional($this->lastSeen($station, $latest, $connectivityConfigs))->toISOString(),
            'components' => $components,
            'presentation_statuses' => ['Healthy', 'Warning', 'Critical', 'Offline'],
        ];
    }

    private function connectivityIntegrity(MonitoringStation $station, Collection $configs, CarbonInterface $freshAfter): array
    {
        if ($configs->isEmpty()) {
            return [
                'status' => $this->normalizeIntegrity($station->connectivity_status),
                'basis' => 'Station connectivity_status; no connectivity config rows are registered.',
            ];
        }

        $latest = $configs->sortByDesc(fn (ConnectivityConfig $config) => $config->last_seen_at?->timestamp ?? 0)->first();
        $sourceState = $latest?->connection_state ?? $latest?->connectivity_status;
        $stale = ! $latest?->last_seen_at || $latest->last_seen_at->lt($freshAfter);
        $status = $stale ? 'Offline' : $this->normalizeIntegrity($sourceState);

        return [
            'status' => $status,
            'basis' => 'Latest connectivity config last_seen_at and connection_state.',
            'last_seen_at' => optional($latest?->last_seen_at)->toISOString(),
            'connection_state' => $sourceState,
            'uplink_state' => $latest?->uplink_state,
        ];
    }

    private function telemetryIntegrity(MonitoringStation $station, Collection $latest, CarbonInterface $freshAfter): array
    {
        if ($station->sensors->isEmpty()) {
            return ['status' => 'Warning', 'basis' => 'No sensors are registered for this station.'];
        }

        $readings = $station->sensors->map(fn (Sensor $sensor) => $latest->get($sensor->id));
        $freshCount = $readings->filter(fn (?TelemetryReading $reading) => $reading?->received_at?->gte($freshAfter))->count();
        $hazardStatus = $this->worstHazard($readings->flatMap(fn (?TelemetryReading $reading) => [
            $reading?->alert_level,
            $reading?->status,
        ]));

        if ($freshCount === 0) {
            return ['status' => 'Offline', 'basis' => 'No fresh latest telemetry readings.'];
        }

        return [
            'status' => $hazardStatus === 'AWAS' ? 'Critical' : ($freshCount < $station->sensors->count() ? 'Warning' : 'Healthy'),
            'basis' => 'Fresh latest readings and current telemetry alert/status values.',
            'fresh_readings' => $freshCount,
            'registered_sensors' => $station->sensors->count(),
        ];
    }

    private function deviceIntegrity(MonitoringStation $station, Collection $latest, CarbonInterface $freshAfter): array
    {
        $sensorCount = $station->sensors->count();

        if ($sensorCount === 0) {
            return ['status' => 'Warning', 'basis' => 'No instruments/sensors are registered.'];
        }

        $staleCount = $station->sensors
            ->filter(fn (Sensor $sensor) => ! $latest->get($sensor->id)?->received_at?->gte($freshAfter))
            ->count();

        return [
            'status' => $staleCount === 0 ? 'Healthy' : ($staleCount === $sensorCount ? 'Offline' : 'Warning'),
            'basis' => 'Instrument health is inferred from sensor telemetry freshness only.',
            'registered_instruments' => $sensorCount,
            'stale_instruments' => $staleCount,
        ];
    }

    private function stationHazard(MonitoringStation $station, Collection $latest): array
    {
        $sources = $station->sensors->map(function (Sensor $sensor) use ($latest, $station) {
            $reading = $latest->get($sensor->id);
            $state = $this->worstHazard(collect([
                $reading?->alert_level,
                $reading?->status,
                $sensor->alert_level,
                $sensor->status,
            ]));

            if ($state === 'NORMAL') {
                return null;
            }

            return [
                'sensor_id' => $sensor->id,
                'sensor_code' => $sensor->sensor_code,
                'sensor_label' => $sensor->parameter ?: $sensor->type,
                'data_logger_id' => $reading?->data_logger_id ?? $sensor->data_logger_id,
                'data_logger_code' => $reading?->dataLogger?->logger_code ?? $sensor->dataLogger?->logger_code,
                'state' => $state,
                'alert_level' => $reading?->alert_level ?? $sensor->alert_level,
                'status' => $reading?->status ?? $sensor->status,
                'threshold' => $sensor->threshold,
                'rule' => $sensor->rule,
                'timestamp' => optional($reading?->received_at ?? $sensor->last_seen_at)->toISOString(),
                'parameter_values' => $this->normalizedParameterValues($sensor, $reading)->values()->all(),
                'station_code' => $station->station_code,
                'station_name' => $station->name,
                'corridor_code' => $station->corridor?->corridor_code,
                'coordinate' => $station->coordinate,
                'latitude' => $station->latitude !== null ? (float) $station->latitude : null,
                'longitude' => $station->longitude !== null ? (float) $station->longitude : null,
                'basis' => 'Latest telemetry alert/status for this registered sensor.',
            ];
        })->filter()->values();

        $levels = $station->sensors->flatMap(function (Sensor $sensor) use ($latest) {
            $reading = $latest->get($sensor->id);

            return [
                $reading?->alert_level,
                $reading?->status,
                $sensor->alert_level,
                $sensor->status,
            ];
        });
        $classifications = $station->hydrometEwsRelationships
            ->flatMap(fn (HydrometEwsRelationship $relationship) => $relationship->hazardClassifications);

        return [
            'state' => $this->worstHazard($levels),
            'basis' => 'Latest telemetry alert/status state; Phase 5 classifications are configuration-only.',
            'affected_sensors' => $sources->all(),
            'configured_classifications' => $classifications->map(fn (HydrometHazardClassification $classification) => [
                'classification_code' => $classification->classification_code,
                'sensor_id' => $classification->sensor_id,
                'sensor_code' => $classification->sensor?->sensor_code,
                'parameter' => $classification->parameter,
                'reading_method' => $classification->reading_method,
                'threshold_config' => $classification->threshold_config,
                'hazard_levels' => $classification->hazard_levels,
                'evaluation_engine' => $classification->evaluation_engine,
            ])->values()->all(),
        ];
    }

    private function stationWarningState(MonitoringStation $station): array
    {
        $configs = $station->hydrometEwsRelationships
            ->flatMap(fn (HydrometEwsRelationship $relationship) => $relationship->wdamConfigs);

        return [
            'activation_status' => $configs->contains('automatic_activation_enabled', true)
                ? 'automatic_configured'
                : ($configs->isNotEmpty() ? 'manual_or_disabled' : 'not_configured'),
            'warning_station_assignments' => $configs->map(fn (HydrometWdamConfig $config) => [
                'wdam_code' => $config->wdam_code,
                'warning_station_id' => $config->warning_station_id,
                'warning_station_code' => $config->warningStation?->station_code,
                'assignment_enabled' => $config->warning_station_assignment_enabled,
                'automatic_activation_enabled' => $config->automatic_activation_enabled,
                'authority_method' => $config->authority_method,
                'execution_implemented' => false,
            ])->values()->all(),
        ];
    }

    private function corridorHazard(CorridorMonitoring $corridor, Collection $stationRows): array
    {
        $stations = $stationRows
            ->filter(fn (array $row) => (int) ($row['station']['corridor_id'] ?? 0) === (int) $corridor->id)
            ->values();

        return [
            'corridor_id' => $corridor->id,
            'corridor_code' => $corridor->corridor_code,
            'hazard_state' => $this->worstHazard($stations->pluck('hazard.state')),
            'station_count' => $stations->count(),
        ];
    }

    private function warningStationStatus(WarningStation $station): array
    {
        $station->loadMissing('devices');
        $expected = $station->devices->where('expected', true);
        $available = $expected->where('availability_state', 'available');

        return [
            'warning_station_id' => $station->id,
            'station_code' => $station->station_code,
            'status' => $station->status,
            'controller_status' => $station->controller_status,
            'presentation_status' => $this->normalizeIntegrity($station->controller_status ?? $station->status),
            'public_warning_enabled' => $station->public_warning_enabled,
            'expected_devices' => $expected->count(),
            'available_devices' => $available->count(),
            'administrative' => $this->administrativeState($station),
        ];
    }

    private function analyticalOutputs(MonitoringStation $station): array
    {
        return collect(['TDE', 'Discharge', 'CFPE'])
            ->map(fn (string $function) => (new ConfigurationOnlyAnalyticalFunctionRunner($function))->run($station))
            ->values()
            ->all();
    }

    private function lastSeen(MonitoringStation $station, Collection $latest, Collection $connectivityConfigs): ?Carbon
    {
        return collect([
            $station->sensors->pluck('last_seen_at')->filter()->max(),
            $latest->pluck('received_at')->filter()->max(),
            $connectivityConfigs->pluck('last_seen_at')->filter()->max(),
        ])->filter()->sortDesc()->first();
    }

    private function normalizeIntegrity(?string $state): string
    {
        $value = strtolower((string) $state);

        if (str_contains($value, 'offline') || str_contains($value, 'down') || str_contains($value, 'lost')) {
            return 'Offline';
        }

        if (str_contains($value, 'critical') || str_contains($value, 'awas') || str_contains($value, 'danger') || str_contains($value, 'error') || str_contains($value, 'failed')) {
            return 'Critical';
        }

        if (str_contains($value, 'warning') || str_contains($value, 'waspada') || str_contains($value, 'siaga') || str_contains($value, 'degraded') || str_contains($value, 'partial') || str_contains($value, 'unknown')) {
            return 'Warning';
        }

        return 'Healthy';
    }

    private function worstIntegrity(Collection $states): string
    {
        return $states
            ->filter()
            ->sortByDesc(fn (string $state) => self::INTEGRITY_RANK[$state] ?? 1)
            ->first() ?? 'Warning';
    }

    private function worstHazard(Collection $states): string
    {
        return $states
            ->filter()
            ->map(fn ($state) => strtoupper((string) $state))
            ->sortByDesc(fn (string $state) => self::HAZARD_RANK[$state] ?? 0)
            ->first() ?: 'NORMAL';
    }
}
