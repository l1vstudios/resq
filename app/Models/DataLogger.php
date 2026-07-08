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
        'logger_status',
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
