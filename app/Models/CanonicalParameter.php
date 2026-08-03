<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanonicalParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_identity',
        'definition',
        'domain',
        'canonical_unit',
        'data_type',
        'measurement_characteristic',
        'is_platform_processed',
        'source_fields',
        'formula',
        'input_requirements',
        'status',
    ];

    protected $casts = [
        'is_platform_processed' => 'boolean',
        'source_fields' => 'array',
        'input_requirements' => 'array',
    ];

    public function sensorMappingProfiles()
    {
        return $this->hasMany(SensorMappingProfile::class);
    }

    public function values()
    {
        return $this->hasMany(CanonicalParameterValue::class);
    }
}
