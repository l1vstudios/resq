<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorridorMonitoring extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'reference_route_id',
        'corridor_code',
        'name',
        'path_coordinates',
        'status',
        'status_metadata',
        'notes',
    ];

    protected $casts = [
        'path_coordinates' => 'array',
        'status_metadata' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function referenceRoute()
    {
        return $this->belongsTo(ReferenceRoute::class, 'reference_route_id');
    }

    public function referencePoints()
    {
        return $this->hasMany(ReferencePoint::class, 'corridor_id');
    }

    public function stationSpatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class, 'corridor_id');
    }

    public function hydrometEwsRelationships()
    {
        return $this->hasMany(HydrometEwsRelationship::class, 'corridor_id');
    }
}
