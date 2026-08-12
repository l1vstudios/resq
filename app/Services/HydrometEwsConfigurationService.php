<?php

namespace App\Services;

use App\Models\CanonicalParameter;
use App\Models\CorridorMonitoring;
use App\Models\HydrometEwsRelationship;
use App\Models\HydrometHazardClassification;
use App\Models\HydrometWdamConfig;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\WarningStation;

class HydrometEwsConfigurationService
{
    public function relationshipConfiguration(HydrometEwsRelationship $relationship): array
    {
        $relationship->loadMissing([
            'project',
            'corridor',
            'monitoringStation.sensors.mappingProfile.canonicalParameter',
            'warningStation',
            'hazardClassifications.sensor',
            'hazardClassifications.canonicalParameter',
            'wdamConfigs.warningStation',
        ]);

        return [
            'relationship' => $this->relationshipRow($relationship),
            'hazard_classifications' => $relationship->hazardClassifications
                ->map(fn (HydrometHazardClassification $classification) => $this->classificationRow($classification))
                ->values()
                ->all(),
            'wdam' => $relationship->wdamConfigs
                ->map(fn (HydrometWdamConfig $config) => $this->wdamRow($config))
                ->values()
                ->all(),
            'scientific_rules' => [
                'evaluation_engine' => 'configuration_only',
                'unresolved' => self::unresolvedScientificRules(),
            ],
        ];
    }

    public function createOrUpdateRelationship(array $data): HydrometEwsRelationship
    {
        $project = Project::findOrFail($data['project_id']);
        $corridor = CorridorMonitoring::findOrFail($data['corridor_id']);
        $monitoringStation = MonitoringStation::findOrFail($data['monitoring_station_id']);
        $warningStation = ! empty($data['warning_station_id']) ? WarningStation::findOrFail($data['warning_station_id']) : null;

        $this->assertSameProject($project, $corridor->project_id);
        $this->assertSameProject($project, $monitoringStation->project_id ?: $monitoringStation->workspace?->project_id);

        if ($monitoringStation->corridor_id !== null) {
            $this->assertSameResource($monitoringStation->corridor_id, $corridor->id);
        }

        if ($warningStation) {
            $this->assertSameProject($project, $warningStation->project_id ?: $warningStation->workspace?->project_id);
        }

        return HydrometEwsRelationship::updateOrCreate(
            ['relationship_code' => $data['relationship_code']],
            [
                'project_id' => $project->id,
                'corridor_id' => $corridor->id,
                'monitoring_station_id' => $monitoringStation->id,
                'warning_station_id' => $warningStation?->id,
                'name' => $data['name'],
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    public function createOrUpdateHazardClassification(array $data): HydrometHazardClassification
    {
        $relationship = HydrometEwsRelationship::with(['project', 'monitoringStation'])->findOrFail($data['hydromet_ews_relationship_id']);
        $sensor = ! empty($data['sensor_id']) ? Sensor::findOrFail($data['sensor_id']) : null;
        $canonical = ! empty($data['canonical_parameter_id']) ? CanonicalParameter::findOrFail($data['canonical_parameter_id']) : null;

        if ($sensor) {
            $this->assertSameResource($sensor->monitoring_station_id, $relationship->monitoring_station_id);
        }

        return HydrometHazardClassification::updateOrCreate(
            ['classification_code' => $data['classification_code']],
            [
                'project_id' => $relationship->project_id,
                'hydromet_ews_relationship_id' => $relationship->id,
                'corridor_id' => $relationship->corridor_id,
                'monitoring_station_id' => $relationship->monitoring_station_id,
                'sensor_id' => $sensor?->id,
                'canonical_parameter_id' => $canonical?->id,
                'parameter' => $data['parameter'] ?? $canonical?->field_identity ?? $sensor?->parameter ?? $sensor?->type,
                'reading_method' => $data['reading_method'],
                'threshold_config' => $data['threshold_config'] ?? null,
                'hazard_levels' => $this->hazardLevels($data['hazard_levels'] ?? null),
                'unresolved_business_rules' => $data['unresolved_business_rules'] ?? self::unresolvedScientificRules(),
                'evaluation_engine' => 'configuration_only',
                'status' => $data['status'] ?? 'draft',
            ]
        );
    }

    public function createOrUpdateWdamConfig(array $data): HydrometWdamConfig
    {
        $relationship = HydrometEwsRelationship::findOrFail($data['hydromet_ews_relationship_id']);
        $warningStation = ! empty($data['warning_station_id'])
            ? WarningStation::findOrFail($data['warning_station_id'])
            : $relationship->warningStation;

        if ($warningStation) {
            $this->assertSameResource($warningStation->project_id ?: $warningStation->workspace?->project_id, $relationship->project_id);
        }

        return HydrometWdamConfig::updateOrCreate(
            ['wdam_code' => $data['wdam_code']],
            [
                'project_id' => $relationship->project_id,
                'hydromet_ews_relationship_id' => $relationship->id,
                'warning_station_id' => $warningStation?->id,
                'dashboard_notification_enabled' => (bool) ($data['dashboard_notification_enabled'] ?? true),
                'registered_recipients' => $data['registered_recipients'] ?? [],
                'sms_enabled' => (bool) ($data['sms_enabled'] ?? false),
                'sms_provider_ref' => $data['sms_provider_ref'] ?? null,
                'whatsapp_enabled' => (bool) ($data['whatsapp_enabled'] ?? false),
                'whatsapp_provider_ref' => $data['whatsapp_provider_ref'] ?? null,
                'warning_station_assignment_enabled' => (bool) ($data['warning_station_assignment_enabled'] ?? false),
                'automatic_activation_enabled' => (bool) ($data['automatic_activation_enabled'] ?? false),
                'authority_method' => $data['authority_method'] ?? 'manual_authority',
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    public function relationshipRow(HydrometEwsRelationship $relationship): array
    {
        return [
            'id' => $relationship->id,
            'project_id' => $relationship->project_id,
            'project_code' => $relationship->project?->project_code,
            'relationship_code' => $relationship->relationship_code,
            'name' => $relationship->name,
            'corridor_id' => $relationship->corridor_id,
            'corridor_code' => $relationship->corridor?->corridor_code,
            'monitoring_station_id' => $relationship->monitoring_station_id,
            'monitoring_station_code' => $relationship->monitoringStation?->station_code,
            'warning_station_id' => $relationship->warning_station_id,
            'warning_station_code' => $relationship->warningStation?->station_code,
            'status' => $relationship->status,
            'notes' => $relationship->notes,
        ];
    }

    public function classificationRow(HydrometHazardClassification $classification): array
    {
        return [
            'id' => $classification->id,
            'classification_code' => $classification->classification_code,
            'project_id' => $classification->project_id,
            'relationship_id' => $classification->hydromet_ews_relationship_id,
            'corridor_id' => $classification->corridor_id,
            'monitoring_station_id' => $classification->monitoring_station_id,
            'sensor_id' => $classification->sensor_id,
            'sensor_code' => $classification->sensor?->sensor_code,
            'canonical_parameter_id' => $classification->canonical_parameter_id,
            'canonical_parameter' => $classification->canonicalParameter?->field_identity,
            'parameter' => $classification->parameter,
            'reading_method' => $classification->reading_method,
            'threshold_config' => $classification->threshold_config ?? [],
            'hazard_levels' => $classification->hazard_levels ?? HydrometHazardClassification::defaultHazardLevels(),
            'evaluation_engine' => $classification->evaluation_engine,
            'unresolved_business_rules' => $classification->unresolved_business_rules ?? self::unresolvedScientificRules(),
            'status' => $classification->status,
        ];
    }

    public function wdamRow(HydrometWdamConfig $config): array
    {
        return [
            'id' => $config->id,
            'wdam_code' => $config->wdam_code,
            'project_id' => $config->project_id,
            'relationship_id' => $config->hydromet_ews_relationship_id,
            'dashboard_notification' => $config->dashboard_notification_enabled,
            'registered_recipients' => $config->registered_recipients ?? [],
            'sms' => [
                'enabled' => $config->sms_enabled,
                'provider_ref' => $config->sms_provider_ref,
                'provider_selected' => $config->sms_provider_ref !== null,
            ],
            'whatsapp' => [
                'enabled' => $config->whatsapp_enabled,
                'provider_ref' => $config->whatsapp_provider_ref,
                'provider_selected' => $config->whatsapp_provider_ref !== null,
            ],
            'warning_activation' => [
                'warning_station_assignment' => $config->warning_station_assignment_enabled,
                'warning_station_id' => $config->warning_station_id,
                'warning_station_code' => $config->warningStation?->station_code,
                'automatic_activation' => $config->automatic_activation_enabled,
                'authority_method' => $config->authority_method,
                'execution_implemented' => false,
            ],
            'status' => $config->status,
            'notes' => $config->notes,
        ];
    }

    public function hazardLevels(?array $levels): array
    {
        $defaults = HydrometHazardClassification::defaultHazardLevels();

        foreach ($levels ?? [] as $level => $config) {
            $normalized = strtoupper((string) $level);
            if (! array_key_exists($normalized, $defaults)) {
                continue;
            }

            $defaults[$normalized] = array_merge(
                $defaults[$normalized],
                is_array($config) ? $config : ['threshold' => $config]
            );
        }

        return $defaults;
    }

    public static function unresolvedScientificRules(): array
    {
        return [
            'Absolute threshold comparison direction and inclusivity are not defined.',
            'Accumulative windowing and reset periods are not defined.',
            'Moving Average window length and sampling policy are not defined.',
            'Probability model inputs and calibration rules are not defined.',
            'Hazard escalation/de-escalation persistence rules are not defined.',
        ];
    }

    private function assertSameProject(Project $project, int|string|null $resourceProjectId): void
    {
        $this->assertSameResource($project->id, $resourceProjectId);
    }

    private function assertSameResource(int|string|null $expected, int|string|null $actual): void
    {
        abort_if($expected === null || $actual === null || (int) $expected !== (int) $actual, 403);
    }
}
