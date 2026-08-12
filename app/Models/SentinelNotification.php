<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentinelNotification extends Model
{
    use HasFactory;

    public const CATEGORY_OPERATIONAL = 'Operational';

    public const CATEGORY_SYSTEM = 'System';

    protected $fillable = [
        'user_id',
        'client_id',
        'project_id',
        'corridor_id',
        'monitoring_station_id',
        'warning_station_id',
        'category',
        'event_type',
        'title',
        'body',
        'source_context',
        'occurred_at',
        'read_at',
    ];

    protected $casts = [
        'source_context' => 'array',
        'occurred_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function corridor()
    {
        return $this->belongsTo(CorridorMonitoring::class, 'corridor_id');
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function warningStation()
    {
        return $this->belongsTo(WarningStation::class);
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
