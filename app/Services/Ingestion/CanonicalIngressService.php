<?php

namespace App\Services\Ingestion;

use App\Services\Canonicalization\CanonicalProcessingResult;
use App\Services\Canonicalization\CanonicalProcessingService;
use Brick\Math\BigDecimal;
use Throwable;

final class CanonicalIngressService
{
    public function __construct(
        private readonly RawIngestionService $rawIngestion,
        private readonly CanonicalProcessingService $canonicalProcessing,
        private readonly LegacyTelemetryProjectionService $legacyProjection,
        private readonly IngressRolloutService $rollout,
    ) {}

    public function ingest(IngressSubmission $submission): IngressResult
    {
        try {
            $capture = $this->rawIngestion->capture($submission->envelope);
        } catch (RawEventConflictException $exception) {
            $this->rollout->recordEvidence($submission->pathKey, [
                ...$this->payloadFacts($submission),
                'capture_outcome' => 'conflict',
                'reason_code' => 'logical_event_key_conflict',
                'raw_ingestion_event_id' => $exception->event->id,
            ]);

            throw $exception;
        }

        if ($capture->idempotent || $capture->event->receipt_status !== 'accepted') {
            $this->recordEvidence(
                $submission,
                new IngressResult($submission->pathKey, $capture),
                $capture->idempotent ? 'idempotent' : 'rejected',
                $capture->event->failure_reason,
            );

            return new IngressResult($submission->pathKey, $capture);
        }

        $canonical = null;
        $processingFailure = null;

        try {
            $canonical = $this->canonicalProcessing->process($capture->event, 'live');
        } catch (Throwable $exception) {
            $processingFailure = substr($exception->getMessage(), 0, 4000);
            $this->rawIngestion->recordProcessingOutcome($capture->event, 'canonical_failed', $processingFailure);
        }

        if ($canonical?->mapped && ! $canonical->projectableValue) {
            $this->rawIngestion->recordProcessingOutcome($capture->event, $canonical->run->status, $canonical->run->reason);

            $result = new IngressResult(
                $submission->pathKey,
                $capture,
                $canonical,
                processingFailure: $processingFailure,
            );
            $this->recordEvidence($submission, $result, 'accepted', $canonical->run->reason);

            return $result;
        }

        [$projectionValue, $projectedCanonicalValueId, $canonicalWinner] = $this->projectionCandidate($submission, $canonical);

        if ($projectionValue === null || ! $submission->sensor) {
            $this->rawIngestion->recordProcessingOutcome(
                $capture->event,
                $canonical?->run->status ?? ($processingFailure ? 'canonical_failed' : 'legacy_unprojectable'),
                $canonical?->run->reason ?? $processingFailure,
            );

            $result = new IngressResult(
                $submission->pathKey,
                $capture,
                $canonical,
                processingFailure: $processingFailure,
            );
            $this->recordEvidence($submission, $result, 'accepted', $processingFailure);

            return $result;
        }

        try {
            $projected = $this->legacyProjection->project(
                $submission,
                $projectionValue,
                $capture->event->received_at,
                $projectedCanonicalValueId,
            );
            if (! $projected) {
                $this->rawIngestion->recordProcessingOutcome(
                    $capture->event,
                    $canonicalWinner ? 'canonical_projection_superseded' : 'legacy_projection_skipped',
                    $canonicalWinner ? 'Canonical head changed before compatibility projection.' : null,
                );

                $result = new IngressResult(
                    $submission->pathKey,
                    $capture,
                    $canonical,
                    processingFailure: $processingFailure,
                );
                $this->recordEvidence($submission, $result, 'accepted', $canonicalWinner ? 'canonical_projection_superseded' : 'legacy_projection_skipped');

                return $result;
            }
            $this->rawIngestion->recordProcessingOutcome(
                $capture->event,
                $processingFailure
                    ? 'legacy_projected_after_canonical_failure'
                    : ($canonicalWinner ? 'canonical_projected' : 'legacy_projected'),
                $processingFailure,
            );
        } catch (Throwable $exception) {
            $this->rawIngestion->recordProcessingOutcome(
                $capture->event,
                'projection_failed',
                substr($exception->getMessage(), 0, 4000),
            );
            $this->recordEvidence(
                $submission,
                new IngressResult(
                    $submission->pathKey,
                    $capture,
                    $canonical,
                    processingFailure: substr($exception->getMessage(), 0, 4000),
                ),
                'accepted',
                'projection_failed',
            );

            throw $exception;
        }

        $result = new IngressResult(
            $submission->pathKey,
            $capture,
            $canonical,
            true,
            $projectionValue,
            $projectedCanonicalValueId,
            $processingFailure,
        );
        $this->recordEvidence($submission, $result, 'accepted', $processingFailure);

        return $result;
    }

    private function projectionCandidate(IngressSubmission $submission, ?CanonicalProcessingResult $canonical): array
    {
        $compatibilityMode = $this->rollout->compatibilityMode($submission->pathKey);

        if ($compatibilityMode === 'canonical') {
            $canonicalValue = $canonical?->projectableValue;
            $projectionValue = $canonicalValue?->value_decimal;

            return $projectionValue === null
                ? [null, null, false]
                : [(string) $projectionValue, (int) $canonicalValue->id, true];
        }

        if (! array_key_exists('value', $submission->compatibilityCandidate ?? [])) {
            return [null, null, false];
        }

        $value = $submission->compatibilityCandidate['value'];

        return [$value === null ? null : (string) $value, null, false];
    }

    private function recordEvidence(
        IngressSubmission $submission,
        IngressResult $result,
        string $captureOutcome,
        ?string $reason,
    ): void {
        $canonical = $result->canonical;
        $values = $canonical?->values ?? collect();
        $valueCount = $values->where('status', 'value')->count();
        $failureStatuses = ['conversion_failure', 'invalid', 'non_finite', 'overflow', 'failed'];
        $failureCount = $values->whereIn('status', $failureStatuses)->count()
            + ($result->processingFailure !== null ? 1 : 0);
        [$parityStatus, $legacyValue, $canonicalValue, $difference] = $this->parityFacts($submission, $canonical);

        $this->rollout->recordEvidence($submission->pathKey, [
            ...$this->payloadFacts($submission),
            'capture_outcome' => $captureOutcome,
            'reason_code' => $this->reasonCode($reason),
            'raw_ingestion_event_id' => $result->capture->event->id,
            'canonical_processing_run_id' => $canonical?->run->id,
            'mapping_profile_version_id' => $canonical?->run->mapping_profile_version_id,
            'mapped' => $canonical?->mapped,
            'canonical_value_count' => $valueCount,
            'canonical_non_value_count' => max(0, $values->count() - $valueCount - $failureCount),
            'canonical_failure_count' => $failureCount,
            'compatibility_eligible' => $this->compatibilityEligible($submission, $canonical),
            'compatibility_projected' => $result->projected,
            'parity_status' => $parityStatus,
            'legacy_value_decimal' => $legacyValue,
            'canonical_value_decimal' => $canonicalValue,
            'parity_difference_decimal' => $difference,
        ]);
    }

    private function payloadFacts(IngressSubmission $submission): array
    {
        return [
            'payload_classification' => $submission->envelope->payloadClassification,
            'payload_size' => $submission->envelope->payloadSize(),
            'payload_sha256' => $submission->envelope->payloadHash(),
        ];
    }

    private function compatibilityEligible(IngressSubmission $submission, ?CanonicalProcessingResult $canonical): bool
    {
        if ($this->rollout->compatibilityMode($submission->pathKey) === 'canonical') {
            return $canonical?->projectableValue !== null;
        }

        return array_key_exists('value', $submission->compatibilityCandidate ?? [])
            && $submission->compatibilityCandidate['value'] !== null;
    }

    private function parityFacts(IngressSubmission $submission, ?CanonicalProcessingResult $canonical): array
    {
        $legacy = $submission->compatibilityCandidate['value'] ?? null;
        $current = $canonical?->projectableValue?->value_decimal;

        if ($legacy === null || $current === null) {
            return ['not_applicable', $this->boundedDecimal($legacy), $this->boundedDecimal($current), null];
        }

        try {
            $legacyDecimal = BigDecimal::of((string) $legacy);
            $canonicalDecimal = BigDecimal::of((string) $current);
            $difference = $canonicalDecimal->minus($legacyDecimal);

            return [
                $difference->isZero() ? 'match' : 'difference',
                $this->boundedDecimal((string) $legacyDecimal),
                $this->boundedDecimal((string) $canonicalDecimal),
                $this->boundedDecimal((string) $difference),
            ];
        } catch (Throwable) {
            return ['not_comparable', $this->boundedDecimal($legacy), $this->boundedDecimal($current), null];
        }
    }

    private function boundedDecimal(mixed $value): ?string
    {
        return $value === null ? null : substr((string) $value, 0, 120);
    }

    private function reasonCode(?string $reason): ?string
    {
        if ($reason === null || trim($reason) === '') {
            return null;
        }

        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', trim($reason)));

        return substr(trim($normalized, '_'), 0, 100) ?: null;
    }
}
