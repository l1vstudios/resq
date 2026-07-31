<?php

namespace App\Services\Ingestion;

use App\Models\RawIngestionEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class RawIngestionService
{
    public function capture(RawEventEnvelope $envelope): RawCaptureResult
    {
        try {
            return DB::transaction(function () use ($envelope) {
                $existing = $this->findExisting($envelope, true);

                if ($existing) {
                    return $this->resolveExisting($existing, $envelope);
                }

                $event = RawIngestionEvent::create([
                    'source_type' => $envelope->sourceType,
                    'source_id' => $envelope->sourceId,
                    'logical_event_key' => $envelope->logicalEventKey,
                    'project_id' => $envelope->projectId,
                    'monitoring_station_id' => $envelope->monitoringStationId,
                    'data_logger_id' => $envelope->dataLoggerId,
                    'sensor_id' => $envelope->sensorId,
                    'transport' => $envelope->transport,
                    'envelope_version' => $envelope->envelopeVersion,
                    'payload_classification' => $envelope->payloadClassification,
                    'content_type' => $envelope->contentType,
                    'content_encoding' => $envelope->contentEncoding,
                    'payload' => $envelope->exactPayload,
                    'payload_hash' => $envelope->payloadHash(),
                    'payload_size' => $envelope->payloadSize(),
                    'inspection_payload' => $envelope->inspectionPayload,
                    'source_snapshot' => $envelope->sourceSnapshot,
                    'observed_at' => $envelope->observedAt,
                    'observed_at_provenance' => $envelope->observedAtProvenance,
                    'observed_at_fallback' => $envelope->observedAtFallback,
                    'received_at' => CarbonImmutable::now('UTC'),
                    'receipt_status' => $envelope->receiptStatus,
                    'failure_reason' => $envelope->failureReason,
                ]);

                foreach ($envelope->items as $index => $item) {
                    $rawBytes = array_key_exists('raw_bytes', $item) ? (string) $item['raw_bytes'] : null;

                    $event->items()->create([
                        'item_key' => (string) ($item['item_key'] ?? $index),
                        'source_parameter' => $item['source_parameter'] ?? null,
                        'raw_value' => array_key_exists('raw_value', $item) ? (string) $item['raw_value'] : null,
                        'raw_bytes' => $rawBytes,
                        'raw_hash' => $rawBytes !== null ? hash('sha256', $rawBytes) : null,
                        'register_address' => $item['register_address'] ?? null,
                        'register_count' => $item['register_count'] ?? null,
                        'metadata' => $item['metadata'] ?? null,
                        'status' => $item['status'] ?? 'received',
                        'reason' => $item['reason'] ?? null,
                    ]);
                }

                return new RawCaptureResult($event->load('items'), false);
            }, 3);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findExisting($envelope);
            if (! $existing) {
                throw $exception;
            }

            return $this->resolveExisting($existing, $envelope);
        }
    }

    public function recordProcessingOutcome(
        RawIngestionEvent $event,
        string $status,
        ?string $failureReason = null,
    ): RawIngestionEvent {
        $event->forceFill([
            'processing_status' => $status,
            'processed_at' => CarbonImmutable::now('UTC'),
            'failure_reason' => $failureReason,
        ])->save();

        return $event->refresh();
    }

    private function findExisting(RawEventEnvelope $envelope, bool $lock = false): ?RawIngestionEvent
    {
        $query = RawIngestionEvent::query()
            ->where('source_type', $envelope->sourceType)
            ->where('source_id', $envelope->sourceId)
            ->where('logical_event_key', $envelope->logicalEventKey);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function resolveExisting(
        RawIngestionEvent $existing,
        RawEventEnvelope $envelope,
    ): RawCaptureResult {
        if (hash_equals($existing->payload_hash, $envelope->payloadHash())) {
            return new RawCaptureResult($existing->loadMissing('items'), true);
        }

        throw new RawEventConflictException(
            $existing,
            $envelope->payloadHash(),
        );
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return (string) ($exception->errorInfo[0] ?? $exception->getCode()) === '23000';
    }
}
