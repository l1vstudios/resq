<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'monitoring_station_id',
        'station_code',
        'name',
        'zone_id',
        'administrative_location',
        'coordinate',
        'latitude',
        'longitude',
        'controller_id',
        'controller_model',
        'controller_vendor',
        'controller_status',
        'registration_status',
        'registered_at',
        'registered_by_user_id',
        'output_devices',
        'status',
        'service_status',
        'service_period_start',
        'service_period_end',
        'entitlement',
        'package_status',
        'administrative_attention',
        'public_warning_enabled',
        'ack_response',
        'notes',
    ];

    protected $casts = [
        'output_devices' => 'array',
        'public_warning_enabled' => 'boolean',
        'registered_at' => 'datetime',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
    ];

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function spatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class);
    }

    public function telemetryConfigs()
    {
        return $this->hasMany(WarningStationTelemetryConfig::class);
    }

    public function devices()
    {
        return $this->hasMany(WarningStationDevice::class);
    }

    public function deviceHeartbeats()
    {
        return $this->hasMany(WarningStationDeviceHeartbeat::class);
    }

    public function hydrometEwsRelationships()
    {
        return $this->hasMany(HydrometEwsRelationship::class);
    }

    public function hydrometWdamConfigs()
    {
        return $this->hasMany(HydrometWdamConfig::class);
    }
}
