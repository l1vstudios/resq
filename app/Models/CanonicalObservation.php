<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanonicalObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'canonical_observation_uid',
        'monitoring_station_id',
        'data_logger_id',
        'sensor_id',
        'domain',
        'observed_at',
        'received_at',
        'field_values',
        'field_units',
        'field_origins',
        'field_quality',
        'processing_statuses',
        'quality_status',
        'completeness_status',
        'processing_status',
        'raw_data_ingestion_id',
        'sensor_mapping_profile_id',
        'traceability',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'received_at' => 'datetime',
        'field_values' => 'array',
        'field_units' => 'array',
        'field_origins' => 'array',
        'field_quality' => 'array',
        'processing_statuses' => 'array',
        'traceability' => 'array',
    ];

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function dataLogger()
    {
        return $this->belongsTo(DataLogger::class);
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function rawDataIngestion()
    {
        return $this->belongsTo(RawDataIngestion::class);
    }

    public function sensorMappingProfile()
    {
        return $this->belongsTo(SensorMappingProfile::class);
    }

    public function parameterValues()
    {
        return $this->hasMany(CanonicalParameterValue::class);
    }
}
