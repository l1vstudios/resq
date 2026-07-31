<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalReplayItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['processed_at' => 'datetime'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(RawIngestionEvent::class, 'raw_ingestion_event_id');
    }
}
