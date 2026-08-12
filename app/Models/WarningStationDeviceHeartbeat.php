<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningStationDeviceHeartbeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'warning_station_id',
        'warning_station_device_id',
        'device_code',
        'device_type',
        'availability_state',
        'health_state',
        'observed_at',
        'received_at',
        'health_payload',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'received_at' => 'datetime',
        'health_payload' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }

    public function device()
    {
        return $this->belongsTo(WarningStationDevice::class, 'warning_station_device_id');
    }
}
