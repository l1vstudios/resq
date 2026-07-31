<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class IngressVerificationAttestation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'passed_count' => 'integer',
        'failed_count' => 'integer',
        'verified_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ingress verification attestations are immutable.'));
        static::deleting(fn () => throw new LogicException('Ingress verification attestations cannot be deleted.'));
    }

    public function rolloutState(): BelongsTo
    {
        return $this->belongsTo(IngressRolloutState::class, 'path_key', 'path_key');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
