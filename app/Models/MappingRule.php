<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MappingRule extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['missing_markers' => 'array', 'metadata' => 'array'];

    protected static function booted(): void
    {
        $assertDraft = function (self $rule) {
            $version = $rule->version()->first();
            if ($version && $version->status !== 'draft') {
                throw new LogicException('Rules in a published mapping version are immutable.');
            }
        };
        static::creating($assertDraft);
        static::updating($assertDraft);
        static::deleting($assertDraft);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MappingProfileVersion::class, 'mapping_profile_version_id');
    }

    public function sourceUnit(): BelongsTo
    {
        return $this->belongsTo(CanonicalUnit::class, 'source_unit_id');
    }

    public function canonicalParameter(): BelongsTo
    {
        return $this->belongsTo(CanonicalParameter::class);
    }

    public function canonicalDefinition(): BelongsTo
    {
        return $this->belongsTo(CanonicalParameterVersion::class, 'canonical_parameter_version_id');
    }
}
