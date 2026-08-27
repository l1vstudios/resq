<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqttConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'configuration_code', 'name', 'broker_url', 'username',
        'password_ciphertext', 'consumer_enabled', 'consumer_topic', 'consumer_qos',
        'example_payload', 'sensor_code_path', 'producer_enabled', 'producer_topic',
        'producer_qos', 'producer_retain', 'publish_canonical', 'publish_warning',
        'canonical_parameter_ids', 'warning_levels', 'canonical_template',
        'warning_template', 'is_active', 'connection_status', 'last_connected_at',
        'last_received_at', 'last_published_at', 'last_error', 'runtime_metrics',
    ];

    protected $hidden = ['password_ciphertext'];

    protected $casts = [
        'consumer_enabled' => 'boolean',
        'producer_enabled' => 'boolean',
        'producer_retain' => 'boolean',
        'publish_canonical' => 'boolean',
        'publish_warning' => 'boolean',
        'is_active' => 'boolean',
        'example_payload' => 'array',
        'canonical_parameter_ids' => 'array',
        'warning_levels' => 'array',
        'runtime_metrics' => 'array',
        'last_connected_at' => 'datetime',
        'last_received_at' => 'datetime',
        'last_published_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function outboxMessages()
    {
        return $this->hasMany(MqttOutboxMessage::class);
    }
}
