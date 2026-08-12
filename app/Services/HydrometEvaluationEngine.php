<?php

namespace App\Services;

use App\Models\HydrometHazardClassification;

interface HydrometEvaluationEngine
{
    public function evaluate(HydrometHazardClassification $classification, array $readings = []): array;
}
