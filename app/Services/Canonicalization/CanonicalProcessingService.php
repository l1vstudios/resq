<?php

namespace App\Services\Canonicalization;

use App\Models\CanonicalCurrentHead;
use App\Models\CanonicalObservation;
use App\Models\CanonicalProcessingRun;
use App\Models\CanonicalValue;
use App\Models\MappingAssignment;
use App\Models\MappingProfileVersion;
use App\Models\MappingRule;
use App\Models\RawIngestionEvent;
use App\Models\RawIngestionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CanonicalProcessingService
{
    public function __construct(private readonly DeterministicTransformer $transformer) {}

    public function process(RawIngestionEvent $event, string $runMode = 'live', ?MappingProfileVersion $forcedVersion = null): CanonicalProcessingResult
    {
        $event->loadMissing('items');
        $assignment = $forcedVersion ? null : $this->assignment($event);
        $version = $forcedVersion
            ? $forcedVersion->loadMissing(['profile', 'rules.sourceUnit', 'rules.canonicalParameter', 'rules.canonicalDefinition.unit'])
            : $assignment?->activeVersion()->with(['profile', 'rules.sourceUnit', 'rules.canonicalParameter', 'rules.canonicalDefinition.unit'])->first();
        $runKey = hash('sha256', implode('|', [$event->id, $version?->id ?? 'unmapped', DeterministicTransformer::ENGINE_VERSION, $runMode, $event->payload_classification]));

        return DB::transaction(function () use ($event, $version, $runKey, $runMode) {
            $existingRun = CanonicalProcessingRun::query()->where('run_key', $runKey)->lockForUpdate()->first();
            if ($existingRun && $existingRun->finished_at) {
                $values = CanonicalValue::query()->where('canonical_processing_run_id', $existingRun->id)->get();

                return new CanonicalProcessingResult($existingRun, $values, null, true, $version !== null);
            }

            $run = $existingRun ?: CanonicalProcessingRun::query()->create([
                'run_key' => $runKey,
                'raw_ingestion_event_id' => $event->id,
                'mapping_profile_version_id' => $version?->id,
                'engine_version' => DeterministicTransformer::ENGINE_VERSION,
                'run_mode' => $runMode,
                'status' => $version ? 'processing' : 'unmapped',
                'reason' => $version ? null : 'No active exact-source mapping assignment.',
                'started_at' => now(),
            ]);

            if (! $version) {
                $run->update(['finished_at' => now()]);

                return new CanonicalProcessingResult($run, collect(), null, false, false);
            }

            $observation = CanonicalObservation::query()->firstOrCreate(
                ['raw_ingestion_event_id' => $event->id],
                [
                    'observation_key' => $event->source_type.':'.$event->source_id.':'.$event->logical_event_key,
                    'source_type' => $event->sensor_id ? 'sensor' : 'data_logger',
                    'source_id' => $event->sensor_id ?: $event->data_logger_id ?: $event->source_id,
                    'project_id' => $event->project_id,
                    'monitoring_station_id' => $event->monitoring_station_id,
                    'data_logger_id' => $event->data_logger_id,
                    'sensor_id' => $event->sensor_id,
                    'observed_at' => $event->observed_at,
                    'received_at' => $event->received_at,
                ]
            );

            $values = collect();
            $projectable = null;
            foreach ($version->rules as $rule) {
                $item = $this->matchingItem($event->items, $rule);
                if (! $item) {
                    continue;
                }
                $payloadSemantics = $this->payloadSemantics($event, $item);
                $processingKey = hash('sha256', implode('|', [$item->id, $rule->id, $version->id, DeterministicTransformer::ENGINE_VERSION, $runMode, $payloadSemantics]));
                $value = CanonicalValue::query()->where('processing_key', $processingKey)->first();
                if ($value) {
                    $values->push($value);

                    continue;
                }

                $result = $this->transform($event, $item, $rule, $version->profile->profile_key.'/v'.$version->version, $runMode, $payloadSemantics);
                $head = CanonicalCurrentHead::query()
                    ->where('source_type', $observation->source_type)
                    ->where('source_id', $observation->source_id)
                    ->where('canonical_parameter_id', $rule->canonical_parameter_id)
                    ->lockForUpdate()->first();
                $revision = (int) CanonicalValue::query()
                    ->where('canonical_observation_id', $observation->id)
                    ->where('canonical_parameter_id', $rule->canonical_parameter_id)
                    ->max('revision') + 1;
                $willWin = $result->isValue() && $this->wins($event->observed_at->getTimestamp(), $version->version, $revision, $head);

                $value = CanonicalValue::query()->create([
                    'processing_key' => $processingKey,
                    'canonical_observation_id' => $observation->id,
                    'canonical_processing_run_id' => $run->id,
                    'raw_ingestion_event_id' => $event->id,
                    'raw_ingestion_item_id' => $item->id,
                    'mapping_profile_version_id' => $version->id,
                    'mapping_rule_id' => $rule->id,
                    'canonical_parameter_id' => $rule->canonical_parameter_id,
                    'canonical_parameter_version_id' => $rule->canonical_parameter_version_id,
                    'canonical_unit_id' => $rule->canonicalDefinition->canonical_unit_id,
                    'domain' => $rule->canonicalParameter->domain,
                    'data_type' => $rule->canonicalDefinition->data_type,
                    'value_decimal' => $result->valueDecimal,
                    'value_text' => $result->valueText,
                    'value_boolean' => $result->valueBoolean,
                    'status' => $result->status,
                    'quality' => $result->isValue() ? 'valid' : 'not_available',
                    'reason' => $result->reason,
                    'origin' => $result->origin,
                    'revision' => $revision,
                    'supersedes_id' => $willWin ? $head?->canonical_value_id : null,
                    'observed_at' => $event->observed_at,
                    'received_at' => $event->received_at,
                    'processed_at' => now(),
                    'stage_trace' => $result->stages,
                    'engine_version' => $result->engineVersion,
                    'run_mode' => $runMode,
                ]);
                $values->push($value);

                if ($willWin) {
                    if (! $head) {
                        $head = new CanonicalCurrentHead([
                            'source_type' => $observation->source_type,
                            'source_id' => $observation->source_id,
                            'canonical_parameter_id' => $rule->canonical_parameter_id,
                        ]);
                    }
                    $head->fill([
                        'canonical_value_id' => $value->id,
                        'winner_observed_at' => $value->observed_at,
                        'winner_mapping_version' => $version->version,
                        'winner_revision' => $value->revision,
                    ])->save();
                    $projectable ??= $value;
                }
            }

            $status = $values->isEmpty() ? 'unmapped_items' : ($values->every(fn (CanonicalValue $value) => $value->status === 'value') ? 'completed' : 'completed_with_outcomes');
            $run->update(['status' => $status, 'reason' => $values->isEmpty() ? 'No mapping rule matched a raw item.' : null, 'value_count' => $values->count(), 'finished_at' => now()]);

            return new CanonicalProcessingResult($run->fresh(), $values, $projectable, false, true);
        });
    }

    private function assignment(RawIngestionEvent $event): ?MappingAssignment
    {
        if ($event->sensor_id) {
            $sensor = MappingAssignment::query()->where('scope_key', 'sensor:'.$event->sensor_id)->first();
            if ($sensor) {
                return $sensor;
            }
        }

        return $event->data_logger_id
            ? MappingAssignment::query()->where('scope_key', 'data_logger:'.$event->data_logger_id)->first()
            : null;
    }

    private function matchingItem(Collection $items, MappingRule $rule): ?RawIngestionItem
    {
        return $items->first(function (RawIngestionItem $item) use ($rule) {
            if ($rule->source_item_key && $rule->source_item_key !== $item->item_key) {
                return false;
            }

            return $rule->source_parameter === $item->source_parameter;
        });
    }

    private function transform(RawIngestionEvent $event, RawIngestionItem $item, MappingRule $rule, string $mappingIdentity, string $runMode, string $payloadSemantics): TransformationResult
    {
        $bytes = $item->getRawOriginal('raw_bytes');
        $binary = $payloadSemantics === 'raw' && $bytes !== null;
        $semanticInput = $payloadSemantics === 'pre_normalized'
            ? $this->preNormalizedValue($item)
            : ($binary ? (string) $bytes : (string) $item->raw_value);

        return $this->transformer->transform(new TransformationRequest(
            raw: $semanticInput,
            inputMode: $binary ? 'binary' : 'text',
            parser: $rule->parser,
            byteOffset: $binary ? (int) $rule->byte_offset : 0,
            length: $binary ? (int) $rule->byte_length : null,
            byteOrder: $rule->byte_order,
            wordOrder: $rule->word_order,
            scale: $rule->scale,
            offset: $rule->offset,
            sourceUnitCode: $rule->sourceUnit?->code,
            targetUnitCode: $rule->canonicalDefinition?->unit?->code,
            missingMarkers: $rule->missing_markers ?? [],
            canonicalParameterKey: $rule->canonicalParameter->key,
            outputPrecision: $rule->canonicalDefinition->output_precision,
            roundingMode: $rule->canonicalDefinition->rounding_mode,
            origin: $rule->origin,
            mappingVersionIdentity: $mappingIdentity,
            runMode: $runMode,
            targetDataType: $rule->canonicalDefinition->data_type,
            payloadSemantics: $payloadSemantics,
            evidenceHash: $event->payload_hash,
        ));
    }

    private function payloadSemantics(RawIngestionEvent $event, RawIngestionItem $item): string
    {
        $metadata = $item->metadata ?? [];
        $itemSemantics = $metadata['payload_semantics'] ?? null;

        return in_array($itemSemantics, ['raw', 'pre_normalized'], true)
            ? $itemSemantics
            : $event->payload_classification;
    }

    private function preNormalizedValue(RawIngestionItem $item): string
    {
        $metadata = $item->metadata ?? [];
        foreach (['pre_normalized_value', 'value_text'] as $key) {
            if (array_key_exists($key, $metadata) && $metadata[$key] !== null) {
                return is_bool($metadata[$key])
                    ? ($metadata[$key] ? '1' : '0')
                    : (string) $metadata[$key];
            }
        }

        return $item->raw_value === null ? '' : (string) $item->raw_value;
    }

    private function wins(int $candidateTimestamp, int $mappingVersion, int $revision, ?CanonicalCurrentHead $head): bool
    {
        if (! $head) {
            return true;
        }
        $winnerTime = $head->winner_observed_at->getTimestamp();

        return [$candidateTimestamp, $mappingVersion, $revision]
            > [$winnerTime, $head->winner_mapping_version, $head->winner_revision];
    }
}
