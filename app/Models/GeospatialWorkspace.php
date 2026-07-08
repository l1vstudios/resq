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
}
