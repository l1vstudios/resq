<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'monitoring_station_id',
        'warning_station_id',
        'mst_prefix_id',
        'slave_id',
        'address',
        'function_code',
        'quantity',
        'poll_interval_ms',
        'sensor_code',
        'type',
        'parameter',
        'weather_parameters',
        'value',
        'threshold',
        'data_type',
        'scale_factor',
        'offset',
        'unit',
        'reading_method',
        'alert_level',
        'rule',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'weather_parameters' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }

    public function mstPrefix()
    {
        return $this->belongsTo(MstPrefix::class);
    }

    public function telemetryReadings()
    {
        return $this->hasMany(TelemetryReading::class);
    }
}
