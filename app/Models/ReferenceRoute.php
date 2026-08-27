<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'route_code',
        'name',
        'route_type',
        'path_coordinates',
        'status',
        'notes',
        'total_length',
        'corridor_code',
        'segment_data',
    ];

    protected $casts = [
        'path_coordinates' => 'array',
        'segment_data' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function corridors()
    {
        return $this->hasMany(CorridorMonitoring::class, 'reference_route_id');
    }

    public function referencePoints()
    {
        return $this->hasMany(ReferencePoint::class, 'reference_route_id');
    }

    public function stationSpatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class, 'reference_route_id');
    }
}
