<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqttOutboxMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'mqtt_configuration_id', 'event_type', 'source_type', 'source_id',
        'fingerprint', 'topic', 'qos', 'retain', 'payload', 'status', 'attempts',
        'available_at', 'published_at', 'last_error',
    ];

    protected $casts = [
        'retain' => 'boolean',
        'payload' => 'array',
        'available_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function configuration()
    {
        return $this->belongsTo(MqttConfiguration::class, 'mqtt_configuration_id');
    }
}
