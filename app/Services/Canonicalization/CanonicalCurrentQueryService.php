<?php

namespace App\Services\Canonicalization;

use App\Models\CanonicalCurrentHead;
use App\Models\CanonicalValue;
use App\Services\Ingestion\IngressRolloutService;
use App\Services\Ingestion\InvalidIngressPathException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class CanonicalCurrentQueryService
{
    private const QUERY_CHUNK_SIZE = 1000;

    public function __construct(private readonly IngressRolloutService $rollout) {}

    /** @return Collection<int, CanonicalCurrentReading> */
    public function forSensors(array $sensorIds): Collection
    {
        $sensorIds = collect($sensorIds)
            ->filter(fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($sensorIds === []) {
            return collect();
        }

        $heads = collect($sensorIds)
            ->chunk(self::QUERY_CHUNK_SIZE)
            ->flatMap(fn (Collection $chunk): Collection => CanonicalCurrentHead::query()
                ->where('source_type', 'sensor')
                ->whereIn('source_id', $chunk->all())
                ->with([
                    'value.parameter',
                    'value.unit',
                    'value.observation',
                    'value.rawEvent',
                    'value.mappingVersion',
                    'value.run',
                ])
                ->orderBy('source_id')
                ->orderBy('canonical_parameter_id')
                ->get())
            ->sortBy([
                ['source_id', 'asc'],
                ['canonical_parameter_id', 'asc'],
            ])
            ->values();
        $readEnabledByPath = [];
        $invalidPathEvents = [];

        return $heads->map(function (CanonicalCurrentHead $head) use (&$readEnabledByPath, &$invalidPathEvents): ?CanonicalCurrentReading {
            $value = $head->value;
            if (! $value || ! $this->hasTypedValue($value) || ! $value->rawEvent || ! $value->parameter || ! $value->unit || ! $value->observation || ! $value->mappingVersion || ! $value->run) {
                return null;
            }

            try {
                $path = $this->rollout->resolvePath($value->rawEvent);
                $readEnabledByPath[$path] ??= $this->rollout->canonicalReadEnabled($path);
            } catch (InvalidIngressPathException $exception) {
                if (! isset($invalidPathEvents[$value->rawEvent->id])) {
                    Log::warning('Canonical current head excluded because its trusted ingress path is invalid.', [
                        'raw_ingestion_event_id' => (int) $value->rawEvent->id,
                        'reason' => $exception->getMessage(),
                    ]);
                    $invalidPathEvents[$value->rawEvent->id] = true;
                }

                return null;
            }

            if (! $readEnabledByPath[$path]) {
                return null;
            }

            return new CanonicalCurrentReading(
                headId: (int) $head->id,
                sourceType: (string) $head->source_type,
                sourceId: (int) $head->source_id,
                parameterId: (int) $value->canonical_parameter_id,
                parameterKey: (string) $value->parameter->key,
                parameterName: (string) $value->parameter->key,
                unitId: (int) $value->canonical_unit_id,
                unitCode: (string) $value->unit->code,
                unitSymbol: (string) $value->unit->symbol,
                dataType: (string) $value->data_type,
                valueDecimal: $value->value_decimal === null ? null : (string) $value->value_decimal,
                valueText: $value->value_text === null ? null : (string) $value->value_text,
                valueBoolean: $value->value_boolean === null ? null : (bool) $value->value_boolean,
                quality: (string) $value->quality,
                status: (string) $value->status,
                reason: $value->reason === null ? null : (string) $value->reason,
                origin: (string) $value->origin,
                observedAt: CarbonImmutable::instance($value->observed_at),
                observationId: (int) $value->canonical_observation_id,
                rawEventId: (int) $value->raw_ingestion_event_id,
                rawItemId: (int) $value->raw_ingestion_item_id,
                dataLoggerId: $value->rawEvent->data_logger_id === null ? null : (int) $value->rawEvent->data_logger_id,
                runId: (int) $value->canonical_processing_run_id,
                mappingVersionId: (int) $value->mapping_profile_version_id,
                canonicalValueId: (int) $value->id,
            );
        })->filter()->values();
    }

    private function hasTypedValue(CanonicalValue $value): bool
    {
        if ($value->status !== 'value') {
            return false;
        }

        return match ($value->data_type) {
            'decimal' => $value->value_decimal !== null,
            'text' => $value->value_text !== null,
            'boolean' => $value->value_boolean !== null,
            default => false,
        };
    }
}
