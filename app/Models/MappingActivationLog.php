<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MappingActivationLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Activation audit entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Activation audit entries cannot be deleted.'));
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(MappingAssignment::class, 'mapping_assignment_id');
    }
}
