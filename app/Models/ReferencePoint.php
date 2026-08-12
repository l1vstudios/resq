<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferencePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'corridor_id',
        'reference_route_id',
        'point_code',
        'name',
        'point_type',
        'coordinate',
        'latitude',
        'longitude',
        'status',
        'notes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }

    public function corridor()
    {
        return $this->belongsTo(CorridorMonitoring::class, 'corridor_id');
    }

    public function referenceRoute()
    {
        return $this->belongsTo(ReferenceRoute::class, 'reference_route_id');
    }

    public function stationSpatialReferences()
    {
        return $this->hasMany(StationSpatialReference::class);
    }
}
