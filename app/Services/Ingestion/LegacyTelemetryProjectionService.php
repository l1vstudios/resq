<?php

namespace App\Services\Ingestion;

use App\Models\CanonicalCurrentHead;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Throwable;

final class LegacyTelemetryProjectionService
{
    public function project(
        IngressSubmission $submission,
        string $value,
        mixed $receivedAt,
        ?int $canonicalValueId,
    ): bool {
        $sensor = $submission->sensor;
        if (! $sensor) {
            return false;
        }

        $candidate = $submission->compatibilityCandidate ?? [];

        return DB::transaction(function () use ($submission, $sensor, $candidate, $value, $receivedAt, $canonicalValueId): bool {
            $lockedSensor = Sensor::query()->whereKey($sensor->id)->lockForUpdate()->first();
            if (! $lockedSensor) {
                return false;
            }

            if ($canonicalValueId !== null) {
                $currentHead = CanonicalCurrentHead::query()
                    ->where('source_type', 'sensor')
                    ->where('source_id', $lockedSensor->id)
                    ->where('canonical_value_id', $canonicalValueId)
                    ->lockForUpdate()
                    ->first();

                if (! $currentHead) {
                    return false;
                }
            }

            $thresholdExceeded = $canonicalValueId === null && array_key_exists('threshold_exceeded', $candidate)
                ? (bool) $candidate['threshold_exceeded']
                : $this->thresholdExceeded($value, $lockedSensor->threshold ?? $lockedSensor->rule);
            $level = $thresholdExceeded ? 'Awas' : 'Normal';

            $lockedSensor->update([
                'value' => $value,
                'alert_level' => $level,
                'status' => $level,
                'last_seen_at' => $receivedAt,
            ]);

            $reading = TelemetryReading::query()
                ->where('sensor_id', $lockedSensor->id)
                ->latest('received_at')
                ->latest()
                ->lockForUpdate()
                ->first();
            $attributes = [
                'sensor_id' => $lockedSensor->id,
                'data_logger_id' => $submission->envelope->dataLoggerId,
                'value' => $value,
                'parameter_values' => $candidate['parameter_values'] ?? null,
                'alert_level' => $level,
                'status' => $level,
                'received_at' => $receivedAt,
            ];

            if ($reading) {
                $reading->update($attributes);
            } else {
                $reading = TelemetryReading::query()->create($attributes);
            }

            TelemetryReading::query()
                ->where('sensor_id', $lockedSensor->id)
                ->whereKeyNot($reading->id)
                ->delete();

            return true;
        });
    }

    private function thresholdExceeded(string $value, ?string $threshold): bool
    {
        if ($threshold === null || trim($threshold) === '') {
            return false;
        }

        try {
            return BigDecimal::of($value)->compareTo(BigDecimal::of(trim($threshold))) > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
