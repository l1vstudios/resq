<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataLogger extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitoring_station_id',
        'logger_code',
        'serial_number',
        'logger_model',
        'vendor',
        'firmware_version',
        'device_label',
        'remote_host',
        'remote_ssh_port',
        'remote_ssh_user',
        'remote_ssh_password',
        'remote_gateway_path',
        'remote_last_tested_at',
        'remote_last_status',
        'remote_last_message',
        'logger_status',
    ];

    protected $casts = [
        'remote_ssh_password' => 'encrypted',
        'remote_last_tested_at' => 'datetime',
    ];

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function connectivityConfigs()
    {
        return $this->hasMany(ConnectivityConfig::class);
    }

    public function credentials()
    {
        return $this->hasMany(DeviceCredential::class);
    }
}
