<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningStationTelemetryConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'warning_station_id',
        'config_code',
        'broker_config_ref',
        'protocol',
        'host_or_endpoint',
        'port',
        'topic',
        'qos',
        'retain',
        'credential_ref',
        'connection_status',
        'last_connected_at',
        'last_seen_at',
        'last_error',
    ];

    protected $casts = [
        'retain' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }
}
