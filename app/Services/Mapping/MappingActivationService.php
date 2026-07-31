<?php

namespace App\Services\Mapping;

use App\Models\DataLogger;
use App\Models\MappingActivationLog;
use App\Models\MappingAssignment;
use App\Models\MappingProfileVersion;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

final class MappingActivationService
{
    public function __construct(private readonly MappingValidationService $validator) {}

    public function activate(string $sourceType, int $sourceId, MappingProfileVersion $version, string $reason, ?int $actorId, string $action = 'activate'): MappingAssignment
    {
        if ($version->status !== 'published') {
            throw new LogicException('Only published versions can be activated.');
        }
        if (trim($reason) === '') {
            throw new LogicException('Activation reason is required.');
        }
        $validation = $this->validator->validate($version);
        if (! $validation['valid']) {
            throw new MappingValidationException($validation['errors']);
        }
        $source = $this->source($sourceType, $sourceId);
        $version->loadMissing('profile');
        if ($sourceType === 'data_logger') {
            $vendorMatches = trim((string) $source->vendor) === '' || strcasecmp($source->vendor, $version->profile->manufacturer) === 0;
            $modelMatches = trim((string) $source->logger_model) === '' || strcasecmp($source->logger_model, $version->profile->device_model) === 0;
            if (! $vendorMatches || ! $modelMatches) {
                throw new LogicException('Published profile manufacturer/model does not match the destination data logger.');
            }
        }
        $snapshot = $this->scopeSnapshot($sourceType, $source);
        $scopeKey = $sourceType.':'.$sourceId;

        return DB::transaction(function () use ($scopeKey, $sourceType, $sourceId, $version, $reason, $actorId, $action, $snapshot) {
            $assignment = MappingAssignment::query()->where('scope_key', $scopeKey)->lockForUpdate()->first();
            $fromVersionId = $assignment?->active_version_id;
            if (! $assignment) {
                $assignment = new MappingAssignment(['scope_key' => $scopeKey, 'source_type' => $sourceType, 'source_id' => $sourceId]);
            }
            $assignment->fill($snapshot + [
                'active_version_id' => $version->id,
                'lock_version' => ((int) ($assignment->lock_version ?? 0)) + 1,
                'activation_reason' => $reason,
                'activated_by' => $actorId,
                'activated_at' => now(),
            ])->save();

            MappingActivationLog::query()->create([
                'mapping_assignment_id' => $assignment->id,
                'from_version_id' => $fromVersionId,
                'to_version_id' => $version->id,
                'action' => $action,
                'reason' => $reason,
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);

            return $assignment->fresh(['activeVersion.profile', 'activationLogs']);
        });
    }

    public function rollback(MappingAssignment $assignment, MappingProfileVersion $target, string $reason, ?int $actorId): MappingAssignment
    {
        $wasPreviouslyAssigned = $assignment->activationLogs()
            ->where(fn ($query) => $query->where('to_version_id', $target->id)->orWhere('from_version_id', $target->id))
            ->exists();
        if (! $wasPreviouslyAssigned) {
            throw new LogicException('Rollback target was never assigned to this source.');
        }

        return $this->activate($assignment->source_type, (int) $assignment->source_id, $target, $reason, $actorId, 'rollback');
    }

    private function source(string $type, int $id): Model
    {
        return match ($type) {
            'data_logger' => DataLogger::query()->with('monitoringStation.workspace')->findOrFail($id),
            'sensor' => Sensor::query()->with(['workspace', 'monitoringStation.dataLoggers'])->findOrFail($id),
            default => throw new LogicException('Source type must be data_logger or sensor.'),
        };
    }

    private function scopeSnapshot(string $type, Model $source): array
    {
        if ($type === 'data_logger') {
            $station = $source->monitoringStation;

            return [
                'project_id' => $station?->workspace?->project_id,
                'monitoring_station_id' => $station?->id,
                'data_logger_id' => $source->id,
                'sensor_id' => null,
            ];
        }

        return [
            'project_id' => $source->workspace?->project_id,
            'monitoring_station_id' => $source->monitoring_station_id,
            'data_logger_id' => $source->monitoringStation?->dataLoggers?->first()?->id,
            'sensor_id' => $source->id,
        ];
    }
}
