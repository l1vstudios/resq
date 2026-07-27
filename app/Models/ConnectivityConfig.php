<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectivityConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_logger_id',
        'connectivity_code',
        'communication_type',
        'protocol',
        'host_or_endpoint',
        'port',
        'topic_or_api_path',
        'gateway_id',
        'serial_port',
        'baud_rate',
        'data_bits',
        'stop_bits',
        'parity',
        'timeout_ms',
        'pin_mapping',
        'monitored_sensor_ids',
        'rednode_host',
        'rednode_ssh_port',
        'rednode_ssh_user',
        'rednode_ssh_password',
        'rednode_gateway_path',
        'rednode_poll_interval_ms',
        'sim_number',
        'imei',
        'apn',
        'connectivity_status',
        'last_seen_at',
        'last_error',
        'last_payload',
    ];

    protected $casts = [
        'monitored_sensor_ids' => 'array',
        'rednode_ssh_password' => 'encrypted',
        'last_seen_at' => 'datetime',
        'last_payload' => 'array',
    ];

    public function dataLogger()
    {
        return $this->belongsTo(DataLogger::class);
    }
}
