<?php

namespace App\Services;

use App\Models\HydrometHazardClassification;

class ConfigurationOnlyHydrometEvaluationEngine implements HydrometEvaluationEngine
{
    public function evaluate(HydrometHazardClassification $classification, array $readings = []): array
    {
        return [
            'evaluation_state' => 'not_implemented',
            'hazard_state' => null,
            'configured_levels' => $classification->hazard_levels ?? HydrometHazardClassification::defaultHazardLevels(),
            'unresolved_business_rules' => $classification->unresolved_business_rules ?? HydrometEwsConfigurationService::unresolvedScientificRules(),
        ];
    }
}
