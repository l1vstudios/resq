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

    protected $casts = [
        'client_id' => 'integer',
    ];

    /**
     * The seeded demo project's internal name is not part of the public UI.
     */
    public function displayName(): ?string
    {
        $name = trim((string) ($this->getRawOriginal('name') ?? $this->attributes['name'] ?? ''));

        return in_array(strtolower($name), ['sentinal project', 'sentinel project'], true)
            ? null
            : $name;
    }

    public function displayLabel(): string
    {
        return $this->displayName()
            ? $this->project_code.' — '.$this->displayName()
            : $this->project_code;
    }

    public function workspaces()
    {
        return $this->hasMany(GeospatialWorkspace::class);
    }

    public function monitoringStations()
    {
        return $this->hasMany(MonitoringStation::class);
    }

    public function assetDevices()
    {
        return $this->hasMany(AssetDevice::class);
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

    public function roleUsers()
    {
        return $this->belongsToMany(User::class, 'user_has_project_roles')
            ->withPivot(['role_id', 'assigned_by'])
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
