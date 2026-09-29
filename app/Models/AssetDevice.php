<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'monitoring_station_id',
        'asset_code',
        'name',
        'asset_type',
        'vendor',
        'model',
        'protocol',
        'serial_port',
        'slave_address',
        'baud_rate',
        'data_bits',
        'parity',
        'stop_bits',
        'timeout_ms',
        'status',
        'last_scanned_at',
        'last_scan_status',
        'last_scan_message',
        'last_scan_payload',
    ];

    protected $casts = [
        'last_scanned_at' => 'datetime',
        'last_scan_payload' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }
}
