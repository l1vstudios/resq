<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_logger_id',
        'credential_code',
        'device_token',
        'mqtt_username',
        'mqtt_password_hash',
        'certificate_ref',
        'credential_status',
        'revoked_at',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function dataLogger()
    {
        return $this->belongsTo(DataLogger::class);
    }
}
