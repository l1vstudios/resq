<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorMappingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_id',
        'profile_code',
        'manufacturer',
        'device_model',
        'communication_path',
        'slave_id',
        'source_parameter',
        'source_unit',
        'register_address',
        'function_code',
        'value_type',
        'data_length',
        'byte_order',
        'scale_factor',
        'offset',
        'value_interpretation',
        'canonical_parameter_id',
        'value_origin',
        'status',
    ];

    protected $casts = [
        'scale_factor' => 'decimal:8',
        'offset' => 'decimal:8',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function canonicalParameter()
    {
        return $this->belongsTo(CanonicalParameter::class);
    }

    public function canonicalObservations()
    {
        return $this->hasMany(CanonicalObservation::class);
    }
}
