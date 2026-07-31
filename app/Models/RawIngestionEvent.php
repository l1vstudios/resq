<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class RawIngestionEvent extends Model
{
    private const MUTABLE_OUTCOME_FIELDS = [
        'processing_status',
        'processed_at',
        'failure_reason',
        'updated_at',
    ];

    protected $fillable = [
        'source_type',
        'source_id',
        'logical_event_key',
        'project_id',
        'monitoring_station_id',
        'data_logger_id',
        'sensor_id',
        'transport',
        'envelope_version',
        'payload_classification',
        'content_type',
        'content_encoding',
        'payload',
        'payload_hash',
        'payload_size',
        'inspection_payload',
        'source_snapshot',
        'observed_at',
        'observed_at_provenance',
        'observed_at_fallback',
        'received_at',
        'processed_at',
        'receipt_status',
        'processing_status',
        'failure_reason',
    ];

    protected $hidden = [
        'payload',
    ];

    protected $casts = [
        'inspection_payload' => 'array',
        'source_snapshot' => 'array',
        'observed_at' => 'immutable_datetime',
        'observed_at_fallback' => 'boolean',
        'received_at' => 'immutable_datetime',
        'processed_at' => 'immutable_datetime',
        'payload_size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $event) {
            $protectedChanges = array_diff(array_keys($event->getDirty()), self::MUTABLE_OUTCOME_FIELDS);

            if ($protectedChanges !== []) {
                throw new LogicException('Raw ingestion evidence is immutable.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Raw ingestion events cannot be deleted through the application.');
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(RawIngestionItem::class);
    }

    public function canonicalValues(): HasMany
    {
        return $this->hasMany(CanonicalValue::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function monitoringStation(): BelongsTo
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function dataLogger(): BelongsTo
    {
        return $this->belongsTo(DataLogger::class);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
