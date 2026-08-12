<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'corridor_id',
        'station_code',
        'name',
        'station_type',
        'coordinate',
        'latitude',
        'longitude',
        'logger_id',
        'logger_status',
        'connectivity_status',
        'registration_status',
        'registered_at',
        'registered_by_user_id',
        'status',
        'service_status',
        'service_period_start',
        'service_period_end',
        'entitlement',
        'package_status',
        'administrative_attention',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
    ];

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function corridor()
    {
        return $this->belongsTo(CorridorMonitoring::class, 'corridor_id');
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function warningStations()
    {
        return $this->hasMany(WarningStation::class);
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function dataLoggers()
    {
        return $this->hasMany(DataLogger::class);
    }

    public function spatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class);
    }

    public function hydrometEwsRelationships()
    {
        return $this->hasMany(HydrometEwsRelationship::class);
    }

    public function functionConfigurations()
    {
        return $this->hasMany(StationFunctionConfiguration::class);
    }
}
