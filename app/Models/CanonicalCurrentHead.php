<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalCurrentHead extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['winner_observed_at' => 'datetime'];

    public function value(): BelongsTo
    {
        return $this->belongsTo(CanonicalValue::class, 'canonical_value_id');
    }
}
