<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeospatialWorkspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_code',
        'name',
        'hazard',
        'province',
        'city',
        'beneficiaries',
        'latitude',
        'longitude',
        'status',
        'basemap_provider',
        'basemap_tile_url',
        'default_zoom',
        'map_bounds',
    ];

    protected $casts = [
        'map_bounds' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function monitoringStations()
    {
        return $this->hasMany(MonitoringStation::class, 'workspace_id');
    }

    public function warningStations()
    {
        return $this->hasMany(WarningStation::class, 'workspace_id');
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class, 'workspace_id');
    }

    public function informationLayers()
    {
        return $this->hasMany(SpatialInformationLayer::class, 'workspace_id');
    }

    public function corridors()
    {
        return $this->hasMany(CorridorMonitoring::class, 'workspace_id');
    }

    public function referenceRoutes()
    {
        return $this->hasMany(ReferenceRoute::class, 'workspace_id');
    }

    public function referencePoints()
    {
        return $this->hasMany(ReferencePoint::class, 'workspace_id');
    }

    public function stationSpatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class, 'workspace_id');
    }
}
