<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'resq_projects';

    protected $fillable = [
        'project_code',
        'name',
        'owner',
        'client_id',
        'project_date',
        'status',
    ];

    public function workspaces()
    {
        return $this->hasMany(GeospatialWorkspace::class);
    }

    public function monitoringStations()
    {
        return $this->hasMany(MonitoringStation::class);
    }

    public function warningStations()
    {
        return $this->hasMany(WarningStation::class);
    }

    public function informationLayers()
    {
        return $this->hasMany(SpatialInformationLayer::class);
    }

    public function corridors()
    {
        return $this->hasMany(CorridorMonitoring::class);
    }

    public function referenceRoutes()
    {
        return $this->hasMany(ReferenceRoute::class);
    }

    public function referencePoints()
    {
        return $this->hasMany(ReferencePoint::class);
    }

    public function stationSpatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class);
    }

    public function hydrometEwsRelationships()
    {
        return $this->hasMany(HydrometEwsRelationship::class);
    }

    public function hydrometHazardClassifications()
    {
        return $this->hasMany(HydrometHazardClassification::class);
    }

    public function hydrometWdamConfigs()
    {
        return $this->hasMany(HydrometWdamConfig::class);
    }

    public function stationFunctionConfigurations()
    {
        return $this->hasMany(StationFunctionConfiguration::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_has_projects', 'project_id', 'user_id')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    public function recoveryAccount()
    {
        return $this->hasOne(ProjectRecoveryAccount::class);
    }

    public function mqttConfigurations()
    {
        return $this->hasMany(MqttConfiguration::class);
    }
}
