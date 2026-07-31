<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CanonicalParameterVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'effective_at' => 'datetime',
        'retired_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Canonical definition versions are immutable. Create a new version.'));
        static::deleting(fn () => throw new LogicException('Canonical definition versions cannot be deleted through the application.'));
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
