<?php

namespace App\Services\Mapping;

use App\Models\MappingProfile;
use App\Models\MappingProfileVersion;
use App\Models\MappingRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class MappingProfileService
{
    public function __construct(private readonly MappingValidationService $validator) {}

    public function createDraft(array $attributes, ?int $actorId): MappingProfileVersion
    {
        return DB::transaction(function () use ($attributes, $actorId) {
            $base = Str::slug($attributes['manufacturer'].'-'.$attributes['device_model']);
            $profile = MappingProfile::query()->create([
                'profile_key' => $base.'-'.Str::lower(Str::random(8)),
                'name' => $attributes['name'],
                'manufacturer' => $attributes['manufacturer'],
                'device_model' => $attributes['device_model'],
                'description' => $attributes['description'] ?? null,
                'created_by' => $actorId,
            ]);

            return $profile->versions()->create(['version' => 1, 'status' => 'draft', 'created_by' => $actorId]);
        });
    }

    public function saveRule(MappingProfileVersion $version, array $attributes, ?MappingRule $rule = null): MappingRule
    {
        if ($version->status !== 'draft') {
            throw new LogicException('Only draft versions can be edited.');
        }
        if ($rule && $rule->mapping_profile_version_id !== $version->id) {
            throw new LogicException('Rule does not belong to this mapping version.');
        }
        $attributes['sort_order'] = $attributes['sort_order'] ?? $rule?->sort_order ?? ($version->rules()->max('sort_order') + 1);

        return $rule
            ? tap($rule)->update($attributes)
            : $version->rules()->create($attributes);
    }

    public function publish(MappingProfileVersion $version, string $reason, ?int $actorId): MappingProfileVersion
    {
        if (trim($reason) === '') {
            throw new MappingValidationException(['Change reason wajib diisi.']);
        }

        return DB::transaction(function () use ($version, $reason, $actorId) {
            $locked = MappingProfileVersion::query()->lockForUpdate()->findOrFail($version->id);
            if ($locked->status !== 'draft') {
                throw new LogicException('Only a draft can be published.');
            }
            $validation = $this->validator->validate($locked);
            if (! $validation['valid']) {
                throw new MappingValidationException($validation['errors']);
            }
            $locked->update([
                'status' => 'published',
                'change_reason' => $reason,
                'validation_snapshot' => $validation,
                'published_by' => $actorId,
                'published_at' => now(),
            ]);

            return $locked->fresh(['profile', 'rules']);
        });
    }

    public function clonePublished(MappingProfileVersion $source, string $reason, ?int $actorId): MappingProfileVersion
    {
        if ($source->status !== 'published') {
            throw new LogicException('Only a published version can be cloned.');
        }

        return DB::transaction(function () use ($source, $reason, $actorId) {
            $profile = MappingProfile::query()->lockForUpdate()->findOrFail($source->mapping_profile_id);
            $next = (int) $profile->versions()->max('version') + 1;
            $draft = $profile->versions()->create([
                'version' => $next,
                'status' => 'draft',
                'change_reason' => trim($reason) !== '' ? $reason : 'Clone from version '.$source->version,
                'created_by' => $actorId,
            ]);
            foreach ($source->rules()->orderBy('sort_order')->get() as $rule) {
                $attributes = $rule->getAttributes();
                unset($attributes['id'], $attributes['mapping_profile_version_id'], $attributes['created_at'], $attributes['updated_at']);
                $attributes['missing_markers'] = $rule->missing_markers;
                $attributes['metadata'] = $rule->metadata;
                $draft->rules()->create($attributes);
            }

            return $draft->fresh(['profile', 'rules']);
        });
    }
}
