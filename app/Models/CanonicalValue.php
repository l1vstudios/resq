<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CanonicalValue extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['value_boolean' => 'boolean', 'observed_at' => 'datetime', 'received_at' => 'datetime', 'processed_at' => 'datetime', 'stage_trace' => 'array'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Canonical values are immutable revisions.'));
        static::deleting(fn () => throw new LogicException('Canonical values cannot be deleted.'));
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(CanonicalObservation::class, 'canonical_observation_id');
    }

    public function rawEvent(): BelongsTo
    {
        return $this->belongsTo(RawIngestionEvent::class, 'raw_ingestion_event_id');
    }

    public function rawItem(): BelongsTo
    {
        return $this->belongsTo(RawIngestionItem::class, 'raw_ingestion_item_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CanonicalProcessingRun::class, 'canonical_processing_run_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MappingRule::class, 'mapping_rule_id');
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingProfileVersion::class, 'mapping_profile_version_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(CanonicalParameter::class, 'canonical_parameter_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CanonicalUnit::class, 'canonical_unit_id');
    }
}
