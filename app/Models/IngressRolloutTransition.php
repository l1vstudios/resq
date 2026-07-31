<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class IngressRolloutTransition extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ingress rollout transitions are immutable.'));
        static::deleting(fn () => throw new LogicException('Ingress rollout transitions cannot be deleted.'));
    }

    public function rolloutState(): BelongsTo
    {
        return $this->belongsTo(IngressRolloutState::class, 'ingress_rollout_state_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
