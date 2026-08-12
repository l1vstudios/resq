<?php

namespace App\Services;

use App\Models\HydrometHazardClassification;
use App\Models\MonitoringStation;
use App\Models\ReferencePoint;
use App\Models\ReferenceRoute;
use App\Models\StationFunctionConfiguration;
use Illuminate\Support\Collection;

class ClientFunctionConfigurationService
{
    public function __construct(
        private readonly MonitoringStationDomainService $stationDomain
    ) {
    }

    public function configurationContext(MonitoringStation $station): array
    {
        $station->loadMissing([
            'project.referenceRoutes',
            'project.referencePoints',
            'workspace.project',
            'corridor',
            'sensors.mappingProfile.canonicalParameter',
            'functionConfigurations',
        ]);

        $domain = $this->stationDomain->stationDomain($station);
        $configurations = $station->functionConfigurations
            ->keyBy('function_name')
            ->map(fn (StationFunctionConfiguration $configuration) => $this->configurationRow($configuration));

        return [
            'station' => $domain['station'],
            'instrumentation' => $domain['instrumentation'],
            'capabilities' => collect($domain['capabilities'])
                ->map(fn (array $capability) => [
                    ...$capability,
                    'configuration' => $configurations->get($capability['function']),
                    'unresolved_rules' => $this->unresolvedRules($capability['function']),
                ])
                ->values()
                ->all(),
            'reading_methods' => HydrometHazardClassification::supportedReadingMethods(),
            'reference_routes' => $station->project?->referenceRoutes
                ->map(fn (ReferenceRoute $route) => [
                    'id' => $route->id,
                    'route_code' => $route->route_code,
                    'name' => $route->name,
                    'route_type' => $route->route_type,
                    'status' => $route->status,
                ])
                ->values()
                ->all() ?? [],
            'reference_points' => $station->project?->referencePoints
                ->map(fn (ReferencePoint $point) => [
                    'id' => $point->id,
                    'point_code' => $point->point_code,
                    'name' => $point->name,
                    'point_type' => $point->point_type,
                    'reference_route_id' => $point->reference_route_id,
                    'status' => $point->status,
                ])
                ->values()
                ->all() ?? [],
        ];
    }

    public function stationRows(Collection $stations): Collection
    {
        return $stations
            ->map(function (MonitoringStation $station) {
                $context = $this->configurationContext($station);
                $available = collect($context['capabilities'])
                    ->filter(fn (array $capability) => $capability['available'])
                    ->pluck('function')
                    ->values();

                return [
                    'station' => [
                        'id' => $station->id,
                        'station_code' => $station->station_code,
                        'name' => $station->name,
                        'project_code' => $station->project?->project_code,
                        'corridor_code' => $station->corridor?->corridor_code,
                    ],
                    'available_functions' => $available->all(),
                ];
            })
            ->values();
    }

    public function createOrUpdate(MonitoringStation $station, string $function, array $data): StationFunctionConfiguration
    {
        abort_unless(in_array($function, StationFunctionConfiguration::supportedFunctions(), true), 404);
        abort_unless($this->functionAvailable($station, $function), 422);

        $projectId = $station->project_id ?: $station->workspace?->project_id;
        abort_unless($projectId, 403);

        $configuration = match ($function) {
            StationFunctionConfiguration::FUNCTION_TDE => $this->tdeConfiguration($data),
            StationFunctionConfiguration::FUNCTION_DISCHARGE => $this->dischargeConfiguration($data),
            StationFunctionConfiguration::FUNCTION_CFPE => $this->cfpeConfiguration($station, $data),
            default => [],
        };
        $activated = (bool) ($data['activate'] ?? false);
        $validated = (bool) ($data['validate'] ?? false) || $activated;

        return StationFunctionConfiguration::updateOrCreate(
            [
                'monitoring_station_id' => $station->id,
                'function_name' => $function,
            ],
            [
                'project_id' => $projectId,
                'reading_method' => $data['reading_method'],
                'configuration' => $configuration,
                'validation_state' => $validated ? 'validated' : 'not_validated',
                'validated_at' => $validated ? now() : null,
                'activated_at' => $activated ? now() : null,
                'unresolved_analytical_rules' => $this->unresolvedRules($function),
                'status' => $activated ? 'active' : ($data['status'] ?? 'draft'),
            ]
        );
    }

    public function functionAvailable(MonitoringStation $station, string $function): bool
    {
        $context = $this->configurationContext($station);

        return (bool) collect($context['capabilities'])
            ->first(fn (array $capability) => $capability['function'] === $function && $capability['available']);
    }

    public function configurationRow(StationFunctionConfiguration $configuration): array
    {
        return [
            'id' => $configuration->id,
            'function_name' => $configuration->function_name,
            'reading_method' => $configuration->reading_method,
            'configuration' => $configuration->configuration ?? [],
            'validation_state' => $configuration->validation_state,
            'validated_at' => optional($configuration->validated_at)->toISOString(),
            'activated_at' => optional($configuration->activated_at)->toISOString(),
            'unresolved_analytical_rules' => $configuration->unresolved_analytical_rules ?? $this->unresolvedRules($configuration->function_name),
            'status' => $configuration->status,
        ];
    }

    public function unresolvedRules(string $function): array
    {
        return SentinelRuntimeReadService::unresolvedAnalyticalRules($function);
    }

    private function tdeConfiguration(array $data): array
    {
        return [
            'data_window' => [
                'value' => (int) $data['data_window_value'],
                'unit' => $data['data_window_unit'],
            ],
            'client_configurable' => ['data_window'],
            'system_internals_locked' => [
                'analytical_matrix',
                'derived_data_processing',
                'system_level_threshold',
            ],
        ];
    }

    private function dischargeConfiguration(array $data): array
    {
        return [
            'cross_sectional_area' => $this->nullableFloat($data['cross_sectional_area'] ?? null),
            'manning_n' => $this->nullableFloat($data['manning_n'] ?? null),
            'coefficient_cd' => $this->nullableFloat($data['coefficient_cd'] ?? null),
            'unit' => $data['unit'],
            'calculation_parameters' => $data['calculation_parameters'] ?? [],
            'calculation_runtime' => 'backend_only',
        ];
    }

    private function cfpeConfiguration(MonitoringStation $station, array $data): array
    {
        $projectId = $station->project_id ?: $station->workspace?->project_id;
        $route = ReferenceRoute::findOrFail($data['reference_route_id']);
        abort_if((int) $route->project_id !== (int) $projectId, 403);

        $bm = ! empty($data['reference_bm_id'])
            ? ReferencePoint::findOrFail($data['reference_bm_id'])
            : null;

        if ($bm) {
            abort_if((int) $bm->project_id !== (int) $projectId, 403);
            abort_if($bm->reference_route_id && (int) $bm->reference_route_id !== (int) $route->id, 403);
        }

        return [
            'reference_route_id' => $route->id,
            'reference_route_code' => $route->route_code,
            'reference_bm_id' => $bm?->id,
            'reference_bm_code' => $bm?->point_code,
            'offset_direction' => $data['offset_direction'],
            'offset_distance' => $this->nullableFloat($data['offset_distance'] ?? null),
            'station_ground_zero_chainage' => $this->nullableFloat($data['station_ground_zero_chainage'] ?? null),
            'uncertainty_factor' => $this->nullableFloat($data['uncertainty_factor'] ?? null),
            'validation_requested' => (bool) ($data['validate'] ?? false),
            'activation_requested' => (bool) ($data['activate'] ?? false),
            'calculation_runtime' => 'backend_only',
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
