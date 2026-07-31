<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RawIngestionItem extends Model
{
    protected $fillable = [
        'item_key',
        'source_parameter',
        'raw_value',
        'raw_bytes',
        'raw_hash',
        'register_address',
        'register_count',
        'metadata',
        'status',
        'reason',
    ];

    protected $hidden = [
        'raw_bytes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'register_address' => 'integer',
        'register_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Raw ingestion items are immutable.');
        });

        static::deleting(function () {
            throw new LogicException('Raw ingestion items cannot be deleted through the application.');
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(RawIngestionEvent::class, 'raw_ingestion_event_id');
    }
}
