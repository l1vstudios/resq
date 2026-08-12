<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningStationDevice extends Model
{
    use HasFactory;

    public const TYPE_WSCP = 'wscp';
    public const TYPE_ASCP = 'ascp';
    public const TYPE_SIREN = 'siren';
    public const TYPE_BEACON = 'beacon';
    public const TYPE_OTHER_OUTPUT = 'other_output_device';

    protected $fillable = [
        'project_id',
        'warning_station_id',
        'device_code',
        'device_type',
        'name',
        'vendor',
        'model',
        'serial_number',
        'expected',
        'availability_state',
        'health_state',
        'last_heartbeat_at',
        'health_payload',
        'status',
        'notes',
    ];

    protected $casts = [
        'expected' => 'boolean',
        'last_heartbeat_at' => 'datetime',
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

    public function heartbeats()
    {
        return $this->hasMany(WarningStationDeviceHeartbeat::class);
    }
}
