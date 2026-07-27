<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelemetryReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_id',
        'data_logger_id',
        'value',
        'parameter_values',
        'alert_level',
        'status',
        'received_at',
    ];

    protected $casts = [
        'parameter_values' => 'array',
        'received_at' => 'datetime',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function dataLogger()
    {
        return $this->belongsTo(DataLogger::class);
    }
}
