<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationFunctionConfiguration extends Model
{
    use HasFactory;

    public const FUNCTION_TDE = 'TDE';
    public const FUNCTION_DISCHARGE = 'Discharge';
    public const FUNCTION_CFPE = 'CFPE';

    protected $fillable = [
        'project_id',
        'monitoring_station_id',
        'function_name',
        'reading_method',
        'configuration',
        'validation_state',
        'validated_at',
        'activated_at',
        'unresolved_analytical_rules',
        'status',
    ];

    protected $casts = [
        'configuration' => 'array',
        'unresolved_analytical_rules' => 'array',
        'validated_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public static function supportedFunctions(): array
    {
        return [
            self::FUNCTION_TDE,
            self::FUNCTION_DISCHARGE,
            self::FUNCTION_CFPE,
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }
}
