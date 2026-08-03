<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawDataIngestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitoring_station_id',
        'data_logger_id',
        'sensor_id',
        'source_device_identity',
        'source_parameter',
        'register_address',
        'function_code',
        'data_type',
        'data_length',
        'byte_order',
        'scale_factor',
        'offset',
        'source_unit',
        'raw_value',
        'payload',
        'raw_data_classification',
        'observed_at',
        'received_at',
        'reception_status',
    ];

    protected $casts = [
        'payload' => 'array',
        'scale_factor' => 'decimal:8',
        'offset' => 'decimal:8',
        'observed_at' => 'datetime',
        'received_at' => 'datetime',
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

    public function canonicalObservations()
    {
        return $this->hasMany(CanonicalObservation::class);
    }
}
