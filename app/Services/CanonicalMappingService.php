<?php

namespace App\Services;

use App\Models\CanonicalObservation;
use App\Models\CanonicalParameterValue;
use App\Models\RawDataIngestion;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CanonicalMappingService
{
    public function activeProfileForSensor(Sensor $sensor): ?SensorMappingProfile
    {
        return $this->activeProfilesForSensor($sensor)->first();
    }

    public function activeProfilesForSensor(Sensor $sensor): Collection
    {
        if (! Schema::hasTable('sensor_mapping_profiles') || ! Schema::hasTable('canonical_parameters')) {
            return collect();
        }

        return SensorMappingProfile::with('canonicalParameter')
            ->where('sensor_id', $sensor->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (SensorMappingProfile $profile) => $this->integerAddress($profile->register_address) ?? PHP_INT_MAX)
            ->values();
    }

    public function rednodeSensorConfig(Sensor $sensor): array
    {
        $profiles = $this->activeProfilesForSensor($sensor);
        $profile = $profiles->first();
        $parameter = $profile?->canonicalParameter;
        $baseAddress = $this->baseAddressForSensor($sensor, $profiles);
        $sensorQuantity = (int) ($sensor->quantity ?? 1);
        $quantity = max($sensorQuantity, $this->mappedQuantity($baseAddress, $profiles));
        $mappedParameters = $this->mappedParametersForRednode($baseAddress, $profiles);

        return [
            'sensor_id' => $sensor->id,
            'sensor_code' => $sensor->sensor_code,
            'sensor_label' => $sensor->parameter ?: $sensor->type,
            'sensor_type' => $sensor->type,
            'parameter' => $sensor->parameter,
            'mapped_parameters' => $mappedParameters,
            'weather_parameters' => $this->weatherParametersForSensor($sensor, $mappedParameters),
            'slave_id' => $profile?->slave_id ?? $sensor->slave_id ?? 1,
            'function_code' => $profile?->function_code ?? $sensor->function_code ?? 'FC03',
            'address' => $baseAddress ?? 0,
            'quantity' => $quantity,
            'poll_interval_ms' => $sensor->poll_interval_ms ?? 1000,
            'data_type' => $profile?->value_type ?? $sensor->data_type ?? 'float32',
            'byte_order' => $profile?->byte_order,
            'scale_factor' => (float) ($profile?->scale_factor ?? $sensor->scale_factor ?? 1),
            'offset' => (float) ($profile?->offset ?? $sensor->offset ?? 0),
            'unit' => $parameter?->canonical_unit ?? $sensor->unit,
            'threshold' => $sensor->threshold,
            'rule' => $sensor->rule,
            'mapping' => $profile ? [
                'profile_id' => $profile->id,
                'profile_code' => $profile->profile_code,
                'source_parameter' => $profile->source_parameter,
                'source_unit' => $profile->source_unit,
                'value_origin' => $profile->value_origin,
                'canonical_parameter_id' => $parameter?->id,
                'canonical_field' => $parameter?->field_identity,
                'canonical_unit' => $parameter?->canonical_unit,
                'canonical_domain' => $parameter?->domain,
            ] : null,
        ];
    }

    private function weatherParametersForSensor(Sensor $sensor, array $mappedParameters): array
    {
        if ($mappedParameters !== []) {
            return collect($mappedParameters)
                ->pluck('parameter')
                ->filter()
                ->values()
                ->all();
        }

        return collect($sensor->weather_parameters ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function mappedQuantity(?int $baseAddress, Collection $profiles): int
    {
        $maxQuantity = 1;

        foreach ($profiles as $profile) {
            $registerIndex = $this->registerIndex($profile, $baseAddress);
            $dataLength = max((int) ($profile->data_length ?? 1), 1);
            $maxQuantity = max($maxQuantity, $registerIndex + $dataLength);
        }

        return $maxQuantity;
    }

    private function mappedParametersForRednode(?int $baseAddress, Collection $profiles): array
    {
        return $profiles
            ->filter(fn (SensorMappingProfile $profile) => $profile->canonicalParameter !== null)
            ->map(function (SensorMappingProfile $profile) use ($baseAddress) {
                $parameter = $profile->canonicalParameter;
                $requirements = $parameter->input_requirements ?? [];

                return [
                    'profile_id' => $profile->id,
                    'profile_code' => $profile->profile_code,
                    'parameter' => $parameter->field_identity,
                    'label' => ucwords(str_replace('_', ' ', $parameter->field_identity)),
                    'canonical_field' => $parameter->field_identity,
                    'canonical_unit' => $parameter->canonical_unit,
                    'canonical_domain' => $parameter->domain,
                    'source_parameter' => $profile->source_parameter,
                    'source_unit' => $profile->source_unit,
                    'register_address' => $profile->register_address,
                    'register_index' => $this->registerIndex($profile, $baseAddress),
                    'function_code' => $profile->function_code,
                    'data_type' => $profile->value_type,
                    'data_length' => max((int) ($profile->data_length ?? 1), 1),
                    'byte_order' => $profile->byte_order,
                    'scale_factor' => (float) ($profile->scale_factor ?? 1),
                    'offset' => (float) ($profile->offset ?? 0),
                    'value_origin' => $profile->value_origin,
                    'requirements' => $requirements,
                    'datasheet_source_url' => $requirements['source_url'] ?? null,
                    'datasheet_reference' => $requirements['source_reference'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function baseAddressForSensor(Sensor $sensor, Collection $profiles): ?int
    {
        $sensorAddress = $this->integerAddress($sensor->address);

        if ($sensorAddress !== null) {
            return $sensorAddress;
        }

        return $profiles
            ->map(fn (SensorMappingProfile $profile) => $this->integerAddress($profile->register_address))
            ->filter(fn (?int $address) => $address !== null)
            ->min();
    }

    private function registerIndex(SensorMappingProfile $profile, ?int $baseAddress): int
    {
        $address = $this->integerAddress($profile->register_address);

        if ($address === null || $baseAddress === null) {
            return 0;
        }

        return max($address - $baseAddress, 0);
    }

    private function integerAddress(mixed $address): ?int
    {
        if ($address === null || $address === '') {
            return null;
        }

        return is_numeric($address) ? (int) $address : null;
    }

    public function storeObservation(
        Sensor $sensor,
        mixed $value,
        ?int $dataLoggerId = null,
        ?CarbonInterface $observedAt = null,
        array $payload = [],
        ?SensorMappingProfile $profileOverride = null
    ): ?CanonicalObservation {
        if (! $this->canonicalTablesReady()) {
            return null;
        }

        $profile = $profileOverride ?: $this->activeProfileForSensor($sensor);
        $parameter = $profile?->canonicalParameter;

        if (! $profile || ! $parameter) {
            return null;
        }

        $receivedAt = Carbon::now();
        $observedAt = $observedAt ? Carbon::parse($observedAt->toDateTimeString()) : $receivedAt->copy();
        $numericValue = $this->applyMappingRules($value, $profile);
        $valueOrigin = $this->canonicalValueOrigin($profile->value_origin);

        $rawDataPayload = [
            'monitoring_station_id' => $sensor->monitoring_station_id,
            'data_logger_id' => $dataLoggerId ?? $sensor->data_logger_id,
            'sensor_id' => $sensor->id,
            'source_device_identity' => $sensor->sensor_code,
            'source_parameter' => $profile->source_parameter,
            'register_address' => $profile->register_address,
            'function_code' => $profile->function_code,
            'data_type' => $profile->value_type,
            'data_length' => $profile->data_length,
            'byte_order' => $profile->byte_order,
            'scale_factor' => $profile->scale_factor ?? 1,
            'offset' => $profile->offset ?? 0,
            'source_unit' => $profile->source_unit,
            'raw_value' => $this->stringValue($value),
            'payload' => $payload ?: null,
            'raw_data_classification' => $profile->value_origin,
            'observed_at' => $observedAt,
            'received_at' => $receivedAt,
            'reception_status' => 'received',
        ];
        $rawData = RawDataIngestion::where('sensor_id', $sensor->id)
            ->where('source_parameter', $profile->source_parameter)
            ->where('register_address', $profile->register_address)
            ->latest('received_at')
            ->latest()
            ->first();

        if ($rawData) {
            $rawData->update($rawDataPayload);
        } else {
            $rawData = RawDataIngestion::create($rawDataPayload);
        }

        $fieldValue = $numericValue ?? $this->stringValue($value);
        $observation = CanonicalObservation::where('sensor_id', $sensor->id)
            ->where('domain', $parameter->domain)
            ->where('sensor_mapping_profile_id', $profile->id)
            ->latest('received_at')
            ->latest()
            ->first() ?: new CanonicalObservation();

        if (! $observation->exists) {
            $observation->canonical_observation_uid = (string) Str::uuid();
        }

        $observation->fill([
            'monitoring_station_id' => $sensor->monitoring_station_id,
            'sensor_id' => $sensor->id,
            'data_logger_id' => $dataLoggerId ?? $sensor->data_logger_id,
            'domain' => $parameter->domain,
            'observed_at' => $observedAt,
            'received_at' => $receivedAt,
            'field_values' => [$parameter->field_identity => $fieldValue],
            'field_units' => [$parameter->field_identity => $parameter->canonical_unit],
            'field_origins' => [$parameter->field_identity => $valueOrigin],
            'field_quality' => [$parameter->field_identity => 'valid'],
            'processing_statuses' => [$parameter->field_identity => 'mapped'],
            'quality_status' => 'valid',
            'completeness_status' => 'complete',
            'processing_status' => 'mapped',
            'raw_data_ingestion_id' => $rawData->id,
            'sensor_mapping_profile_id' => $profile->id,
            'traceability' => [
                'source_parameter' => $profile->source_parameter,
                'source_unit' => $profile->source_unit,
                'canonical_field' => $parameter->field_identity,
                'scale_factor' => (float) ($profile->scale_factor ?? 1),
                'offset' => (float) ($profile->offset ?? 0),
            ],
        ]);
        $observation->save();

        CanonicalObservation::where('sensor_id', $sensor->id)
            ->where('domain', $parameter->domain)
            ->where('sensor_mapping_profile_id', $profile->id)
            ->whereKeyNot($observation->id)
            ->delete();

        RawDataIngestion::where('sensor_id', $sensor->id)
            ->where('source_parameter', $profile->source_parameter)
            ->where('register_address', $profile->register_address)
            ->whereKeyNot($rawData->id)
            ->delete();

        CanonicalParameterValue::updateOrCreate(
            [
                'canonical_observation_id' => $observation->id,
                'canonical_parameter_id' => $parameter->id,
            ],
            [
                'numeric_value' => $numericValue,
                'string_value' => $numericValue === null ? $this->stringValue($value) : null,
                'canonical_unit' => $parameter->canonical_unit,
                'value_origin' => $valueOrigin,
                'quality_status' => 'valid',
                'raw_data_ingestion_id' => $rawData->id,
                'sensor_mapping_profile_id' => $profile->id,
                'traceability' => [
                    'profile_code' => $profile->profile_code,
                    'source_parameter' => $profile->source_parameter,
                ],
            ]
        );

        return $observation;
    }

    public function mappedParameterValue(Sensor $sensor, mixed $value): ?array
    {
        $profile = $this->activeProfileForSensor($sensor);
        $parameter = $profile?->canonicalParameter;

        if (! $profile || ! $parameter) {
            return null;
        }

        $numericValue = $this->applyMappingRules($value, $profile);
        $displayValue = $numericValue !== null
            ? rtrim(rtrim(number_format($numericValue, 4, '.', ''), '0'), '.')
            : $this->stringValue($value);
        $unit = $parameter->canonical_unit ?: $sensor->unit;

        return [
            'label' => $parameter->field_identity,
            'parameter' => $parameter->field_identity,
            'value' => $numericValue,
            'value_text' => $this->withUnit($displayValue ?? '-', $unit),
            'unit' => $unit,
            'domain' => $parameter->domain,
            'origin' => $this->canonicalValueOrigin($profile->value_origin),
            'profile_code' => $profile->profile_code,
        ];
    }

    private function withUnit(mixed $value, ?string $unit): string
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

    private function canonicalTablesReady(): bool
    {
        return Schema::hasTable('raw_data_ingestions')
            && Schema::hasTable('canonical_observations')
            && Schema::hasTable('canonical_parameter_values')
            && Schema::hasTable('sensor_mapping_profiles')
            && Schema::hasTable('canonical_parameters');
    }

    private function applyMappingRules(mixed $value, SensorMappingProfile $profile): ?float
    {
        $numericValue = $this->numericFromText($value);

        if ($numericValue === null) {
            return null;
        }

        $scale = (float) ($profile->scale_factor ?? 1);
        $offset = (float) ($profile->offset ?? 0);

        return ($numericValue * $scale) + $offset;
    }

    private function canonicalValueOrigin(?string $origin): string
    {
        return match ($origin) {
            'device_processed' => 'reidentified_device_processed',
            default => 'reidentified_direct_measurement',
        };
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

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value);
    }
}
