<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HydrometEwsRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'corridor_id',
        'monitoring_station_id',
        'warning_station_id',
        'relationship_code',
        'name',
        'status',
        'notes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function corridor()
    {
        return $this->belongsTo(CorridorMonitoring::class, 'corridor_id');
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }

    public function hazardClassifications()
    {
        return $this->hasMany(HydrometHazardClassification::class);
    }

    public function wdamConfigs()
    {
        return $this->hasMany(HydrometWdamConfig::class);
    }
}
