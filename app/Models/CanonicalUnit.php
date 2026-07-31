<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalUnit extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function outgoingConversions(): HasMany
    {
        return $this->hasMany(CanonicalUnitConversion::class, 'source_unit_id');
    }

    public function incomingConversions(): HasMany
    {
        return $this->hasMany(CanonicalUnitConversion::class, 'target_unit_id');
    }
}
