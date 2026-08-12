<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationSpatialReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'corridor_id',
        'reference_route_id',
        'reference_point_id',
        'monitoring_station_id',
        'warning_station_id',
        'placement_role',
        'station_offset',
        'status',
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

    public function referencePoint()
    {
        return $this->belongsTo(ReferencePoint::class);
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }
}
