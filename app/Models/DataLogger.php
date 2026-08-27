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
        'node_red_mqtt_configuration_id',
        'node_red_publish_topic',
        'node_red_service_name',
        'node_red_user_dir',
        'node_red_environment_file',
        'node_red_restart_command',
        'node_red_last_applied_at',
        'node_red_last_tested_at',
        'node_red_last_status',
        'node_red_last_message',
        'logger_status',
        'poll_interval_ms',
    ];

    protected $casts = [
        'remote_ssh_password' => 'encrypted',
        'remote_last_tested_at' => 'datetime',
        'node_red_last_applied_at' => 'datetime',
        'node_red_last_tested_at' => 'datetime',
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

    public function discoveries()
    {
        return $this->hasMany(DataLoggerDiscovery::class, 'matched_data_logger_id');
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function nodeRedMqttConfiguration()
    {
        return $this->belongsTo(MqttConfiguration::class, 'node_red_mqtt_configuration_id');
    }
}
