<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class MappingProfileVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['validation_snapshot' => 'array', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $version) {
            if ($version->getOriginal('status') === 'published') {
                throw new LogicException('Published mapping versions are immutable. Clone a new draft.');
            }
        });
        static::deleting(fn (self $version) => $version->status !== 'draft'
            ? throw new LogicException('Published mapping versions cannot be deleted.')
            : null);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MappingProfile::class, 'mapping_profile_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(MappingRule::class)->orderBy('sort_order');
    }
}
