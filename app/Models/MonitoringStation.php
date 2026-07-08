<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'station_code',
        'name',
        'coordinate',
        'latitude',
        'longitude',
        'logger_id',
        'logger_status',
        'connectivity_status',
        'status',
    ];

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
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
}
