<?php

namespace App\Services;

use App\Models\MonitoringStation;
use App\Models\StationFunctionConfiguration;
use Throwable;

class TrendDiagnosisAnalyticalFunctionRunner implements AnalyticalFunctionRunner
{
    public function __construct(
        private readonly TrendDiagnosisEvaluator $evaluator,
        private readonly ?StationFunctionConfiguration $configuration = null
    ) {}

    public function run(MonitoringStation $station, array $context = []): array
    {
        try {
            $result = $this->evaluator->evaluate($station, $this->configuration, $context);

            return [
                'function' => StationFunctionConfiguration::FUNCTION_TDE,
                'station_id' => $station->id,
                'execution_state' => $result['evaluation_state'],
                'output' => $result,
                'configuration_status' => $this->configuration?->status ?? 'default_runtime',
                'unresolved_rules' => SentinelRuntimeReadService::unresolvedAnalyticalRules(StationFunctionConfiguration::FUNCTION_TDE),
            ];
        } catch (Throwable $exception) {
            return [
                'function' => StationFunctionConfiguration::FUNCTION_TDE,
                'station_id' => $station->id,
                'execution_state' => 'error',
                'output' => null,
                'configuration_status' => $this->configuration?->status ?? 'default_runtime',
                'error' => $exception->getMessage(),
                'unresolved_rules' => SentinelRuntimeReadService::unresolvedAnalyticalRules(StationFunctionConfiguration::FUNCTION_TDE),
            ];
        }
    }
}
