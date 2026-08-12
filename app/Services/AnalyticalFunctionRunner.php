<?php

namespace App\Services;

use App\Models\MonitoringStation;

interface AnalyticalFunctionRunner
{
    public function run(MonitoringStation $station, array $context = []): array;
}
