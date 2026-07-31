<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CanonicalParameter extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['deprecated_at' => 'datetime'];

    public function versions(): HasMany
    {
        return $this->hasMany(CanonicalParameterVersion::class)->orderByDesc('version');
    }

    public function definition(): HasOne
    {
        return $this->hasOne(CanonicalParameterVersion::class)->ofMany('version', 'max');
    }
}
