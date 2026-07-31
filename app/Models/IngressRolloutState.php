<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngressRolloutState extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'state_changed_at' => 'immutable_datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(IngressRolloutTransition::class)
            ->orderByDesc('created_at');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(IngressRolloutEvidence::class, 'path_key', 'path_key')
            ->orderByDesc('recorded_at');
    }

    public function attestations(): HasMany
    {
        return $this->hasMany(IngressVerificationAttestation::class, 'path_key', 'path_key')
            ->orderByDesc('verified_at');
    }
}
