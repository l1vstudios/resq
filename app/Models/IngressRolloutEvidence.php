<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class IngressRolloutEvidence extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'payload_size' => 'integer',
        'mapped' => 'boolean',
        'canonical_value_count' => 'integer',
        'canonical_non_value_count' => 'integer',
        'canonical_failure_count' => 'integer',
        'compatibility_eligible' => 'boolean',
        'compatibility_projected' => 'boolean',
        'recorded_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ingress rollout evidence is immutable.'));
        static::deleting(fn () => throw new LogicException('Ingress rollout evidence cannot be deleted.'));
    }

    public function rolloutState(): BelongsTo
    {
        return $this->belongsTo(IngressRolloutState::class, 'path_key', 'path_key');
    }

    public function rawEvent(): BelongsTo
    {
        return $this->belongsTo(RawIngestionEvent::class, 'raw_ingestion_event_id');
    }

    public function processingRun(): BelongsTo
    {
        return $this->belongsTo(CanonicalProcessingRun::class, 'canonical_processing_run_id');
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingProfileVersion::class, 'mapping_profile_version_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
