<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HydrometWdamConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'hydromet_ews_relationship_id',
        'warning_station_id',
        'wdam_code',
        'dashboard_notification_enabled',
        'registered_recipients',
        'sms_enabled',
        'sms_provider_ref',
        'whatsapp_enabled',
        'whatsapp_provider_ref',
        'warning_station_assignment_enabled',
        'automatic_activation_enabled',
        'authority_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'dashboard_notification_enabled' => 'boolean',
        'registered_recipients' => 'array',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'warning_station_assignment_enabled' => 'boolean',
        'automatic_activation_enabled' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function relationship()
    {
        return $this->belongsTo(HydrometEwsRelationship::class, 'hydromet_ews_relationship_id');
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }
}
