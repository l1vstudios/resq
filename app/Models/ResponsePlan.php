<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'sensor_id',
        'warning_station_id',
        'dashboard_notif',
        'sms_blasting',
        'warning_station_act',
        'notes',
    ];

    protected $casts = [
        'dashboard_notif' => 'boolean',
        'sms_blasting' => 'boolean',
        'warning_station_act' => 'boolean',
    ];
}
