<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class CanonicalObservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['observed_at' => 'datetime', 'received_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Canonical observations are immutable.'));
        static::deleting(fn () => throw new LogicException('Canonical observations cannot be deleted.'));
    }
}
