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
        'sim_number',
        'imei',
        'apn',
        'connectivity_status',
    ];

    public function dataLogger()
    {
        return $this->belongsTo(DataLogger::class);
    }
}
