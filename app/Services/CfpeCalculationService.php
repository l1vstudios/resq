<?php

namespace App\Services;

use App\Models\ReferencePoint;
use App\Models\StationFunctionConfiguration;

class CfpeCalculationService
{
    /**
     * Calculate CFPE propagation ETA for reference points along a route.
     *
     * @param int $referenceRouteId
     * @param float $stationGroundZeroChainage Chainage of the ground zero station (meters)
     * @param float $flowVelocity Flow velocity in m/s
     * @param float $uncertaintyFactor Uncertainty factor (e.g., 0.30 for ±30%)
     * @param string $propagationDirection 'downstream' or 'upstream'
     * @return array
     */
    public function calculate(
        int $referenceRouteId,
        float $stationGroundZeroChainage,
        float $flowVelocity,
        float $uncertaintyFactor = 0.30,
        string $propagationDirection = 'downstream'
    ): array {
        // Step 1: Load all ReferencePoints on the given route
        $points = ReferencePoint::where('reference_route_id', $referenceRouteId)
            ->whereNotNull('chainage')
            ->get();

        // Step 2: Filter points based on propagation direction
        $filteredPoints = $points->filter(function ($point) use ($stationGroundZeroChainage, $propagationDirection) {
            if ($propagationDirection === 'downstream') {
                return $point->chainage > $stationGroundZeroChainage;
            }

            return $point->chainage < $stationGroundZeroChainage;
        });

        // Step 3: Calculate ETA for each target point
        $results = $filteredPoints->map(function ($point) use ($stationGroundZeroChainage, $flowVelocity, $uncertaintyFactor) {
            $propagationDistance = abs($point->chainage - $stationGroundZeroChainage);
            $basicEtaSeconds = $propagationDistance / $flowVelocity;
            $basicEtaMinutes = $basicEtaSeconds / 60;
            $etaMin = $basicEtaMinutes * (1 - $uncertaintyFactor);
            $etaMax = $basicEtaMinutes * (1 + $uncertaintyFactor);

            $etaMinFloor = (int) floor($etaMin);
            $etaMaxCeil = (int) ceil($etaMax);

            return [
                'reference_point' => $point->point_code,
                'bm_id' => $point->bm_id,
                'chainage' => (float) $point->chainage,
                'distance' => round($propagationDistance, 2),
                'basic_eta_minutes' => round($basicEtaMinutes, 1),
                'eta_min_minutes' => $etaMinFloor,
                'eta_max_minutes' => $etaMaxCeil,
                'eta_range' => "{$etaMinFloor} - {$etaMaxCeil}",
            ];
        });

        // Step 4: Sort by chainage
        $sorted = $propagationDirection === 'downstream'
            ? $results->sortBy('chainage')
            : $results->sortByDesc('chainage');

        return $sorted->values()->toArray();
    }

    /**
     * Calculate CFPE from a StationFunctionConfiguration record.
     *
     * @param StationFunctionConfiguration $config
     * @param float $flowVelocity Flow velocity in m/s
     * @return array
     */
    public function calculateFromConfiguration(StationFunctionConfiguration $config, float $flowVelocity): array
    {
        $configuration = $config->configuration;

        $referenceRouteId = $configuration['reference_route_id'] ?? null;
        $stationGroundZeroChainage = (float) ($configuration['station_ground_zero_chainage'] ?? 0);
        $uncertaintyFactor = (float) ($configuration['uncertainty_factor'] ?? 0.30);
        $propagationDirection = $configuration['propagation_direction'] ?? 'downstream';

        if (!$referenceRouteId) {
            return [];
        }

        return $this->calculate(
            $referenceRouteId,
            $stationGroundZeroChainage,
            $flowVelocity,
            $uncertaintyFactor,
            $propagationDirection
        );
    }
}
