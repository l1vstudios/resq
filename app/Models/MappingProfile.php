<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class MappingProfile extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (self $profile) {
            if ($profile->versions()->where('status', 'published')->exists()
                && $profile->isDirty(['profile_key', 'manufacturer', 'device_model'])) {
                throw new LogicException('Published profile identity is immutable.');
            }
        });
        static::deleting(fn (self $profile) => $profile->versions()->exists()
            ? throw new LogicException('Profiles with version history cannot be deleted.')
            : null);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MappingProfileVersion::class)->orderByDesc('version');
    }
}
