<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HydrometHazardClassification extends Model
{
    use HasFactory;

    public const LEVEL_WASPADA = 'WASPADA';
    public const LEVEL_SIAGA = 'SIAGA';
    public const LEVEL_AWAS = 'AWAS';

    public const METHOD_ABSOLUTE = 'Absolute';
    public const METHOD_ACCUMULATIVE = 'Accumulative';
    public const METHOD_MOVING_AVERAGE = 'Moving Average';
    public const METHOD_PROBABILITY = 'Probability';

    protected $fillable = [
        'project_id',
        'hydromet_ews_relationship_id',
        'corridor_id',
        'monitoring_station_id',
        'sensor_id',
        'canonical_parameter_id',
        'classification_code',
        'parameter',
        'reading_method',
        'threshold_config',
        'hazard_levels',
        'unresolved_business_rules',
        'evaluation_engine',
        'status',
    ];

    protected $casts = [
        'threshold_config' => 'array',
        'hazard_levels' => 'array',
        'unresolved_business_rules' => 'array',
    ];

    public static function supportedReadingMethods(): array
    {
        return [
            self::METHOD_ABSOLUTE,
            self::METHOD_ACCUMULATIVE,
            self::METHOD_MOVING_AVERAGE,
            self::METHOD_PROBABILITY,
        ];
    }

    public static function defaultHazardLevels(): array
    {
        return [
            self::LEVEL_WASPADA => ['level' => self::LEVEL_WASPADA, 'threshold' => null],
            self::LEVEL_SIAGA => ['level' => self::LEVEL_SIAGA, 'threshold' => null],
            self::LEVEL_AWAS => ['level' => self::LEVEL_AWAS, 'threshold' => null],
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function relationship()
    {
        return $this->belongsTo(HydrometEwsRelationship::class, 'hydromet_ews_relationship_id');
    }

    public function corridor()
    {
        return $this->belongsTo(CorridorMonitoring::class, 'corridor_id');
    }

    public function monitoringStation()
    {
        return $this->belongsTo(MonitoringStation::class);
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function canonicalParameter()
    {
        return $this->belongsTo(CanonicalParameter::class);
    }
}
