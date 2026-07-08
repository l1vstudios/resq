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
        'alert_level',
        'status',
        'received_at',
    ];

    protected $casts = [
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
