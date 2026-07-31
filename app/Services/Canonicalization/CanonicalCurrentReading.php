<?php

namespace App\Services\Canonicalization;

use Carbon\CarbonImmutable;

final readonly class CanonicalCurrentReading
{
    public function __construct(
        public int $headId,
        public string $sourceType,
        public int $sourceId,
        public int $parameterId,
        public string $parameterKey,
        public string $parameterName,
        public int $unitId,
        public string $unitCode,
        public string $unitSymbol,
        public string $dataType,
        public ?string $valueDecimal,
        public ?string $valueText,
        public ?bool $valueBoolean,
        public string $quality,
        public string $status,
        public ?string $reason,
        public string $origin,
        public CarbonImmutable $observedAt,
        public int $observationId,
        public int $rawEventId,
        public int $rawItemId,
        public ?int $dataLoggerId,
        public int $runId,
        public int $mappingVersionId,
        public int $canonicalValueId,
    ) {}

    public function value(): string|bool|null
    {
        return match ($this->dataType) {
            'decimal' => $this->valueDecimal,
            'text' => $this->valueText,
            'boolean' => $this->valueBoolean,
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'reading_source' => 'canonical',
            'head_id' => $this->headId,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'canonical_parameter_id' => $this->parameterId,
            'parameter_key' => $this->parameterKey,
            'parameter_name' => $this->parameterName,
            'canonical_unit_id' => $this->unitId,
            'unit_code' => $this->unitCode,
            'unit_symbol' => $this->unitSymbol,
            'data_type' => $this->dataType,
            'value' => $this->value(),
            'value_decimal' => $this->valueDecimal,
            'value_text' => $this->valueText,
            'value_boolean' => $this->valueBoolean,
            'quality' => $this->quality,
            'status' => $this->status,
            'reason' => $this->reason,
            'origin' => $this->origin,
            'observed_at' => $this->observedAt->toISOString(),
            'canonical_observation_id' => $this->observationId,
            'raw_ingestion_event_id' => $this->rawEventId,
            'raw_ingestion_item_id' => $this->rawItemId,
            'data_logger_db_id' => $this->dataLoggerId,
            'canonical_processing_run_id' => $this->runId,
            'mapping_profile_version_id' => $this->mappingVersionId,
            'canonical_value_id' => $this->canonicalValueId,
        ];
    }
}
