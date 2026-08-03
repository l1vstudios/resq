<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanonicalParameterValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'canonical_observation_id',
        'canonical_parameter_id',
        'numeric_value',
        'string_value',
        'canonical_unit',
        'value_origin',
        'quality_status',
        'raw_data_ingestion_id',
        'sensor_mapping_profile_id',
        'traceability',
    ];

    protected $casts = [
        'numeric_value' => 'decimal:8',
        'traceability' => 'array',
    ];

    public function canonicalObservation()
    {
        return $this->belongsTo(CanonicalObservation::class);
    }

    public function canonicalParameter()
    {
        return $this->belongsTo(CanonicalParameter::class);
    }

    public function rawDataIngestion()
    {
        return $this->belongsTo(RawDataIngestion::class);
    }

    public function sensorMappingProfile()
    {
        return $this->belongsTo(SensorMappingProfile::class);
    }
}
