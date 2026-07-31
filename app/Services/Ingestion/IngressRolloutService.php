<?php

namespace App\Services\Ingestion;

use App\Models\IngressRolloutEvidence;
use App\Models\IngressRolloutState;
use App\Models\IngressVerificationAttestation;
use App\Models\RawIngestionEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class IngressRolloutService
{
    private const TRANSITIONS = [
        'expand' => ['shadow'],
        'shadow' => ['verified', 'rolled_back'],
        'verified' => ['cutover', 'rolled_back'],
        'cutover' => ['rolled_back'],
        'rolled_back' => ['shadow'],
    ];

    private const TRANSPORT_PATHS = [
        'http' => 'http_callback',
        'mqtt' => 'mqtt',
        'modbus_tcp' => 'modbus_tcp',
        'modbus_rtu' => 'rednode_callback',
        'rednode' => 'rednode_callback',
        'manual' => 'manual',
    ];

    private const EVIDENCE_FIELDS = [
        'capture_outcome',
        'reason_code',
        'payload_classification',
        'payload_size',
        'payload_sha256',
        'raw_ingestion_event_id',
        'canonical_processing_run_id',
        'mapping_profile_version_id',
        'mapped',
        'canonical_value_count',
        'canonical_non_value_count',
        'canonical_failure_count',
        'compatibility_eligible',
        'compatibility_projected',
        'parity_status',
        'legacy_value_decimal',
        'canonical_value_decimal',
        'parity_difference_decimal',
    ];

    public function transition(string $path, string $targetState, int $actorId, string $reason): IngressRolloutState
    {
        $path = $this->assertPath($path);
        $targetState = $this->assertState($targetState);
        $reason = trim($reason);

        if ($actorId < 1) {
            throw new LogicException('A trusted rollout actor is required.');
        }
        if ($reason === '') {
            throw new LogicException('A rollout transition reason is required.');
        }
        if (strlen($reason) > $this->reasonMaxLength()) {
            throw new LogicException('The rollout transition reason exceeds the configured bound.');
        }

        return DB::transaction(function () use ($path, $targetState, $actorId, $reason) {
            $state = IngressRolloutState::query()
                ->where('path_key', $path)
                ->lockForUpdate()
                ->first();

            if (! $state) {
                throw new LogicException('The trusted ingress path has no rollout state.');
            }

            $fromState = $this->assertState((string) $state->state);
            if (! in_array($targetState, self::TRANSITIONS[$fromState] ?? [], true)) {
                throw new LogicException("Ingress rollout transition {$fromState} -> {$targetState} is not allowed.");
            }
            if ($targetState === 'verified') {
                $this->assertCurrentPassingAttestation($path);
            }

            $changedAt = now();
            $state->fill([
                'state' => $targetState,
                'reason' => $reason,
                'actor_id' => $actorId,
                'state_changed_at' => $changedAt,
            ])->save();
            $state->transitions()->create([
                'path_key' => $path,
                'from_state' => $fromState,
                'to_state' => $targetState,
                'reason' => $reason,
                'actor_id' => $actorId,
                'created_at' => $changedAt,
            ]);

            return $state->fresh(['actor', 'transitions']);
        }, 3);
    }

    public function compatibilityMode(string $path): string
    {
        return $this->state($path)->state === 'cutover' ? 'canonical' : 'legacy';
    }

    public function canonicalReadEnabled(string $path): bool
    {
        return $this->state($path)->state === 'cutover';
    }

    public function state(string $path): IngressRolloutState
    {
        $path = $this->assertPath($path);
        $state = IngressRolloutState::query()->where('path_key', $path)->first();

        if (! $state || ! in_array($state->state, $this->states(), true)) {
            throw new LogicException('The trusted ingress path has no valid rollout state.');
        }

        return $state;
    }

    public function resolvePath(RawIngestionEvent $event): string
    {
        $snapshot = $event->source_snapshot;
        $persistedPath = is_array($snapshot) ? ($snapshot['ingress_path'] ?? null) : null;

        if (is_string($persistedPath) && $persistedPath !== '') {
            return $this->assertPath($persistedPath);
        }

        $fallback = self::TRANSPORT_PATHS[(string) $event->transport] ?? null;
        if (! $fallback) {
            throw new InvalidIngressPathException('The raw event transport has no trusted ingress-path fallback.');
        }

        return $this->assertPath($fallback);
    }

    public function recordEvidence(string $path, array $facts, ?int $actorId = null): IngressRolloutEvidence
    {
        $state = $this->state($path);
        $attributes = array_intersect_key($facts, array_flip(self::EVIDENCE_FIELDS));
        $captureOutcome = trim((string) ($attributes['capture_outcome'] ?? ''));

        if ($captureOutcome === '' || strlen($captureOutcome) > 32) {
            throw new InvalidArgumentException('A bounded capture outcome is required.');
        }
        if (isset($attributes['reason_code']) && strlen((string) $attributes['reason_code']) > 100) {
            throw new InvalidArgumentException('The evidence reason code exceeds 100 bytes.');
        }
        if (isset($attributes['payload_sha256']) && ! preg_match('/\A[0-9a-f]{64}\z/', (string) $attributes['payload_sha256'])) {
            throw new InvalidArgumentException('The evidence payload digest must be lowercase SHA-256.');
        }

        return IngressRolloutEvidence::query()->create($attributes + [
            'path_key' => $state->path_key,
            'rollout_state' => $state->state,
            'actor_id' => $actorId,
            'recorded_at' => now(),
        ]);
    }

    public function evidenceSummary(string $path, CarbonImmutable $fromUtc, CarbonImmutable $toUtc): array
    {
        $path = $this->assertPath($path);
        $fromUtc = $fromUtc->utc();
        $toUtc = $toUtc->utc();
        $maxHours = (int) config('canonical.ingress_rollout.evidence_max_window_hours', 168);

        if ($toUtc->lessThan($fromUtc) || $fromUtc->diffInHours($toUtc) > $maxHours) {
            throw new InvalidArgumentException('Evidence summary requires an ordered bounded UTC interval.');
        }

        return IngressRolloutEvidence::query()
            ->where('path_key', $path)
            ->whereBetween('recorded_at', [$fromUtc, $toUtc])
            ->selectRaw(
                'capture_outcome, payload_classification, parity_status, COUNT(*) AS total, '
                .'SUM(canonical_value_count) AS canonical_value_count, '
                .'SUM(canonical_non_value_count) AS canonical_non_value_count, '
                .'SUM(canonical_failure_count) AS canonical_failure_count'
            )
            ->groupBy('capture_outcome', 'payload_classification', 'parity_status')
            ->orderBy('capture_outcome')
            ->limit((int) config('canonical.ingress_rollout.evidence_max_rows', 1000))
            ->get()
            ->map(fn (IngressRolloutEvidence $row): array => $row->getAttributes())
            ->all();
    }

    public function recordVerificationAttestation(string $path, int $passedCount, int $failedCount, string $resultDigest, int $actorId): IngressVerificationAttestation
    {
        $path = $this->assertPath($path);

        if ($actorId < 1) {
            throw new LogicException('A trusted verification actor is required.');
        }
        if ($passedCount < 1 || $failedCount < 0) {
            throw new InvalidArgumentException('Verification counts must describe at least one passing assertion.');
        }
        if (! preg_match('/\A[0-9a-f]{64}\z/', $resultDigest)) {
            throw new InvalidArgumentException('Verification digest must be lowercase SHA-256.');
        }

        return DB::transaction(fn () => IngressVerificationAttestation::query()->create([
            'path_key' => $path,
            'suite_version' => $this->verificationSuiteVersion(),
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'result_digest' => $resultDigest,
            'actor_id' => $actorId,
            'verified_at' => now(),
        ]), 3);
    }

    public function verificationSuiteVersion(): string
    {
        return (string) config('canonical.ingress_rollout.verification_suite_version');
    }

    private function assertCurrentPassingAttestation(string $path): void
    {
        $attestation = IngressVerificationAttestation::query()
            ->where('path_key', $path)
            ->latest('verified_at')
            ->latest('id')
            ->first();
        $freshAfter = now()->subHours((int) config('canonical.ingress_rollout.attestation_freshness_hours', 24));

        if (! $attestation
            || $attestation->suite_version !== $this->verificationSuiteVersion()
            || $attestation->passed_count < 1
            || $attestation->failed_count !== 0
            || $attestation->verified_at->lt($freshAfter)) {
            throw new LogicException('A recent passing current-suite attestation is required before verification.');
        }
    }

    private function assertPath(string $path): string
    {
        if (! in_array($path, $this->paths(), true)) {
            throw new InvalidIngressPathException('Unsupported trusted ingress path.');
        }

        return $path;
    }

    private function assertState(string $state): string
    {
        if (! in_array($state, $this->states(), true)) {
            throw new LogicException('Unsupported ingress rollout state.');
        }

        return $state;
    }

    private function paths(): array
    {
        return config('canonical.ingress_rollout.paths', []);
    }

    private function states(): array
    {
        return config('canonical.ingress_rollout.states', []);
    }

    private function reasonMaxLength(): int
    {
        return (int) config('canonical.ingress_rollout.reason_max_length', 500);
    }
}
