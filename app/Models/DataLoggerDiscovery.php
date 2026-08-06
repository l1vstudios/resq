<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataLoggerDiscovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'matched_data_logger_id',
        'device_uid',
        'logger_code',
        'serial_number',
        'logger_model',
        'vendor',
        'firmware_version',
        'device_label',
        'hostname',
        'request_ip',
        'mac_addresses',
        'last_payload',
        'last_seen_at',
        'status',
    ];

    protected $casts = [
        'mac_addresses' => 'array',
        'last_payload' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function matchedDataLogger()
    {
        return $this->belongsTo(DataLogger::class, 'matched_data_logger_id');
    }
}
