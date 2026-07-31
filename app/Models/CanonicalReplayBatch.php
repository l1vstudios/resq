<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalReplayBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'observed_from' => 'datetime', 'observed_to' => 'datetime', 'dry_run_summary' => 'array',
        'dry_run_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(MappingProfileVersion::class, 'mapping_profile_version_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CanonicalReplayItem::class)->orderBy('raw_ingestion_event_id');
    }
}
