<?php

namespace App\Services\Replay;

use App\Models\CanonicalProcessingRun;
use App\Models\CanonicalReplayBatch;
use App\Models\CanonicalReplayItem;
use App\Models\MappingProfileVersion;
use App\Models\RawIngestionEvent;
use App\Services\Canonicalization\CanonicalProcessingService;
use App\Services\Canonicalization\DeterministicTransformer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

final class CanonicalReplayService
{
    public function __construct(private readonly CanonicalProcessingService $processor) {}

    public function create(array $attributes, ?int $actorId): CanonicalReplayBatch
    {
        $from = CarbonImmutable::parse($attributes['observed_from'])->utc();
        $to = CarbonImmutable::parse($attributes['observed_to'])->utc();
        if ($to->lessThan($from) || $from->diffInDays($to) > 31) {
            throw new ReplayBoundsException('Replay range harus berurutan dan maksimal 31 hari.');
        }
        $version = MappingProfileVersion::query()->findOrFail($attributes['mapping_profile_version_id']);
        if ($version->status !== 'published') {
            throw new LogicException('Replay requires a published mapping version.');
        }
        $sourceType = $attributes['source_type'];
        $sourceId = (int) $attributes['source_id'];
        if (! in_array($sourceType, ['data_logger', 'sensor'], true) || $sourceId < 1 || trim((string) $attributes['reason']) === '') {
            throw new ReplayBoundsException('Exact source and reason are required.');
        }

        return CanonicalReplayBatch::query()->create([
            'batch_key' => (string) Str::uuid(),
            'scope_key' => $sourceType.':'.$sourceId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'observed_from' => $from,
            'observed_to' => $to,
            'mapping_profile_version_id' => $version->id,
            'reason' => $attributes['reason'],
            'status' => 'draft',
            'max_events' => min(max((int) ($attributes['max_events'] ?? 10000), 1), 10000),
            'created_by' => $actorId,
        ]);
    }

    public function dryRun(CanonicalReplayBatch $batch): array
    {
        if (! in_array($batch->status, ['draft', 'dry_run'], true)) {
            throw new LogicException('Only draft batches can be dry-run.');
        }
        $query = $this->eventQuery($batch);
        $selected = (clone $query)->count();
        if ($selected > $batch->max_events) {
            throw new ReplayBoundsException("Selection {$selected} exceeds batch limit {$batch->max_events}.");
        }
        $eventIds = (clone $query)->pluck('id');
        $unchanged = CanonicalProcessingRun::query()
            ->whereIn('raw_ingestion_event_id', $eventIds)
            ->where('mapping_profile_version_id', $batch->mapping_profile_version_id)
            ->where('engine_version', DeterministicTransformer::ENGINE_VERSION)
            ->whereIn('status', ['completed', 'completed_with_outcomes', 'unmapped_items'])
            ->distinct('raw_ingestion_event_id')->count('raw_ingestion_event_id');
        $summary = ['selected' => $selected, 'unchanged' => $unchanged, 'pending' => $selected - $unchanged];
        $batch->update(['status' => 'dry_run', 'selected_count' => $selected, 'dry_run_summary' => $summary, 'dry_run_at' => now()]);

        return $summary;
    }

    public function execute(CanonicalReplayBatch $batch, ?int $maxEventsThisRun = null): CanonicalReplayBatch
    {
        if (! $batch->dry_run_at || ! in_array($batch->status, ['dry_run', 'paused', 'failed', 'running'], true)) {
            throw new LogicException('Dry-run is required before execute/resume.');
        }
        $batch->loadMissing('version');
        $batch->update(['status' => 'running', 'started_at' => $batch->started_at ?? now()]);
        $limit = $maxEventsThisRun ? max(1, $maxEventsThisRun) : $batch->max_events;
        $events = $this->eventQuery($batch)
            ->when($batch->cursor_raw_event_id, fn (Builder $query) => $query->where('id', '>', $batch->cursor_raw_event_id))
            ->limit($limit)->get();

        foreach ($events as $event) {
            $item = CanonicalReplayItem::query()->firstOrCreate(
                ['canonical_replay_batch_id' => $batch->id, 'raw_ingestion_event_id' => $event->id],
                ['status' => 'pending']
            );
            if (in_array($item->status, ['unchanged', 'corrected', 'skipped'], true)) {
                $batch->update(['cursor_raw_event_id' => $event->id]);

                continue;
            }
            try {
                $existing = CanonicalProcessingRun::query()
                    ->where('raw_ingestion_event_id', $event->id)
                    ->where('mapping_profile_version_id', $batch->mapping_profile_version_id)
                    ->where('engine_version', DeterministicTransformer::ENGINE_VERSION)
                    ->whereIn('status', ['completed', 'completed_with_outcomes', 'unmapped_items'])->first();
                if ($existing) {
                    $item->update(['status' => 'unchanged', 'canonical_processing_run_id' => $existing->id, 'reason' => 'Same mapping/engine already processed.', 'processed_at' => now()]);
                } else {
                    $before = $event->canonicalValues()->count();
                    $result = $this->processor->process($event, 'replay', $batch->version);
                    $newCount = $result->values->count();
                    $item->update([
                        'status' => $newCount > 0 ? 'corrected' : 'skipped',
                        'canonical_processing_run_id' => $result->run->id,
                        'previous_value_count' => $before,
                        'new_value_count' => $newCount,
                        'reason' => $result->run->reason,
                        'processed_at' => now(),
                    ]);
                }
            } catch (Throwable $exception) {
                $item->update(['status' => 'failed', 'reason' => substr($exception->getMessage(), 0, 1000), 'processed_at' => now()]);
            }
            $batch->update(['cursor_raw_event_id' => $event->id]);
        }

        $this->refreshCounters($batch);
        $hasMore = $this->eventQuery($batch)->when($batch->cursor_raw_event_id, fn (Builder $query) => $query->where('id', '>', $batch->cursor_raw_event_id))->exists();
        $batch->update([
            'status' => $hasMore ? 'paused' : ($batch->failed_count > 0 ? 'completed_with_failures' : 'completed'),
            'completed_at' => $hasMore ? null : now(),
        ]);

        return $batch->fresh(['items.event', 'version.profile']);
    }

    private function eventQuery(CanonicalReplayBatch $batch): Builder
    {
        return RawIngestionEvent::query()
            ->where('receipt_status', 'accepted')
            ->whereBetween('observed_at', [$batch->observed_from, $batch->observed_to])
            ->when($batch->source_type === 'sensor', fn (Builder $query) => $query->where('sensor_id', $batch->source_id))
            ->when($batch->source_type === 'data_logger', fn (Builder $query) => $query->where('data_logger_id', $batch->source_id))
            ->orderBy('id');
    }

    private function refreshCounters(CanonicalReplayBatch $batch): void
    {
        $counts = CanonicalReplayItem::query()->where('canonical_replay_batch_id', $batch->id)
            ->selectRaw('status, COUNT(*) aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $batch->update([
            'processed_count' => (int) $counts->sum(),
            'unchanged_count' => (int) ($counts['unchanged'] ?? 0),
            'corrected_count' => (int) ($counts['corrected'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'skipped_count' => (int) ($counts['skipped'] ?? 0),
        ]);
        $batch->refresh();
    }
}
