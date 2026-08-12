<?php

namespace App\Services;

use App\Models\MonitoringStation;

class ConfigurationOnlyAnalyticalFunctionRunner implements AnalyticalFunctionRunner
{
    public function __construct(private readonly string $function)
    {
    }

    public function run(MonitoringStation $station, array $context = []): array
    {
        return [
            'function' => $this->function,
            'station_id' => $station->id,
            'execution_state' => 'not_implemented',
            'output' => null,
            'unresolved_rules' => SentinelRuntimeReadService::unresolvedAnalyticalRules($this->function),
        ];
    }
}
