<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MappingAssignment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['activated_at' => 'datetime'];

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(MappingProfileVersion::class, 'active_version_id');
    }

    public function activationLogs(): HasMany
    {
        return $this->hasMany(MappingActivationLog::class)->orderByDesc('created_at');
    }
}
