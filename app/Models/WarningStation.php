<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'monitoring_station_id',
        'station_code',
        'name',
        'zone_id',
        'coordinate',
        'latitude',
        'longitude',
        'controller_id',
        'controller_model',
        'controller_vendor',
        'controller_status',
        'output_devices',
        'status',
        'public_warning_enabled',
        'ack_response',
    ];

    protected $casts = [
        'output_devices' => 'array',
        'public_warning_enabled' => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }
}
