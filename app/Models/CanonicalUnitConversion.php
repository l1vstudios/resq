<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalUnitConversion extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_approved' => 'boolean'];

    public function sourceUnit(): BelongsTo
    {
        return $this->belongsTo(CanonicalUnit::class, 'source_unit_id');
    }

    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(CanonicalUnit::class, 'target_unit_id');
    }
}
