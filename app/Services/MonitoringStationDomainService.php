<?php

namespace App\Services;

use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\MonitoringStation;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use Illuminate\Support\Collection;

class MonitoringStationDomainService
{
    public function stationDomain(MonitoringStation $station): array
    {
        $station->loadMissing([
            'project',
            'workspace.project',
            'corridor',
            'spatialReferences.corridor',
            'dataLoggers.connectivityConfigs',
            'dataLoggers.credentials',
            'sensors.mappingProfile.canonicalParameter',
        ]);

        $mappings = $this->activeSensorMappings($station);

        return [
            'station' => $this->stationRegistry($station),
            'telemetry' => $this->telemetryConfiguration($station),
            'instrumentation' => $this->instrumentation($station, $mappings),
            'capabilities' => $this->capabilities($station, $mappings),
        ];
    }

    public function stationProjectId(MonitoringStation $station): ?int
    {
        return $station->project_id ?: $station->workspace?->project_id;
    }

    public function stationRegistry(MonitoringStation $station): array
    {
        return [
            'id' => $station->id,
            'project_id' => $this->stationProjectId($station),
            'project_code' => $station->project?->project_code ?? $station->workspace?->project?->project_code,
            'workspace_id' => $station->workspace_id,
            'workspace_code' => $station->workspace?->workspace_code,
            'corridor_id' => $station->corridor_id,
            'station_code' => $station->station_code,
            'name' => $station->name,
            'station_type' => $station->station_type,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'registration_status' => $station->registration_status,
            'registered_at' => optional($station->registered_at)->toISOString(),
            'registered_by_user_id' => $station->registered_by_user_id,
            'status' => $station->status,
        ];
    }

    public function telemetryConfiguration(MonitoringStation $station): array
    {
        return $station->dataLoggers
            ->map(fn (DataLogger $logger) => [
                'data_logger' => [
                    'id' => $logger->id,
                    'logger_code' => $logger->logger_code,
                    'serial_number' => $logger->serial_number,
                    'logger_model' => $logger->logger_model,
                    'device_label' => $logger->device_label,
                    'logger_status' => $logger->logger_status,
                    'last_connected_at' => optional($logger->connectivityConfigs->sortByDesc('last_connected_at')->first()?->last_connected_at)->toISOString(),
                    'last_seen_at' => optional($logger->connectivityConfigs->sortByDesc('last_seen_at')->first()?->last_seen_at)->toISOString(),
                ],
                'connectivity' => $logger->connectivityConfigs
                    ->map(fn (ConnectivityConfig $config) => [
                        'id' => $config->id,
                        'connectivity_code' => $config->connectivity_code,
                        'communication_type' => $config->communication_type,
                        'protocol' => $config->protocol,
                        'broker_or_endpoint' => $config->host_or_endpoint,
                        'port' => $config->port,
                        'topic' => $config->topic_or_api_path,
                        'gateway_id' => $config->gateway_id,
                        'serial_port' => $config->serial_port,
                        'connectivity_status' => $config->connectivity_status,
                        'connection_state' => $config->connection_state ?? $this->normalizedState($config->connectivity_status),
                        'uplink_state' => $config->uplink_state ?? $this->normalizedState($config->connectivity_status),
                        'last_connected_at' => optional($config->last_connected_at)->toISOString(),
                        'last_seen_at' => optional($config->last_seen_at)->toISOString(),
                        'last_error' => $config->last_error,
                        'monitored_sensor_ids' => $config->monitored_sensor_ids ?? [],
                    ])
                    ->values()
                    ->all(),
                'mqtt' => [
                    'reuses_gateway' => true,
                    'broker_url' => $this->mqttBrokerUrl($logger),
                    'topics' => $this->mqttTopics($logger),
                    'credential_usernames' => $logger->credentials
                        ->pluck('mqtt_username')
                        ->filter()
                        ->values()
                        ->all(),
                ],
            ])
            ->values()
            ->all();
    }

    public function instrumentation(MonitoringStation $station, ?Collection $mappings = null): array
    {
        $mappings ??= $this->activeSensorMappings($station);

        return [
            'sensors' => $station->sensors
                ->map(fn (Sensor $sensor) => [
                    'id' => $sensor->id,
                    'sensor_code' => $sensor->sensor_code,
                    'type' => $sensor->type,
                    'parameter' => $sensor->parameter,
                    'data_logger_id' => $sensor->data_logger_id,
                    'status' => $sensor->status,
                    'mapped_parameters' => $mappings
                        ->where('sensor_id', $sensor->id)
                        ->map(fn (SensorMappingProfile $profile) => [
                            'profile_code' => $profile->profile_code,
                            'source_parameter' => $profile->source_parameter,
                            'canonical_field' => $profile->canonicalParameter?->field_identity,
                            'canonical_domain' => $profile->canonicalParameter?->domain,
                            'canonical_unit' => $profile->canonicalParameter?->canonical_unit,
                            'value_origin' => $profile->value_origin,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'canonical_fields' => $this->canonicalFields($mappings),
            'canonical_domains' => $mappings
                ->map(fn (SensorMappingProfile $profile) => $profile->canonicalParameter?->domain)
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    public function capabilities(MonitoringStation $station, ?Collection $mappings = null): array
    {
        $mappings ??= $this->activeSensorMappings($station);
        $fields = $this->canonicalFields($mappings);
        $domains = $mappings
            ->map(fn (SensorMappingProfile $profile) => $profile->canonicalParameter?->domain)
            ->filter()
            ->unique()
            ->values();
        $hasCorridorContext = (bool) $station->corridor_id
            || $station->spatialReferences->contains(fn ($reference) => $reference->corridor_id !== null);

        return [
            [
                'domain' => 'Meteorology',
                'function' => 'TDE',
                'available' => $domains->contains('meteorology'),
                'basis' => 'Available when active sensor mappings include meteorology canonical parameters.',
                'instrumentation' => $fields->intersect($this->meteorologyFields())->values()->all(),
            ],
            [
                'domain' => 'Hydrology',
                'function' => 'Discharge',
                'available' => $fields->contains('WaterDischarge')
                    || ($fields->contains('WaterLevel') && $fields->contains('WaterVelocity')),
                'basis' => 'Available with direct WaterDischarge mapping or WaterLevel and WaterVelocity inputs.',
                'instrumentation' => $fields->intersect(['WaterDischarge', 'WaterLevel', 'WaterVelocity'])->values()->all(),
            ],
            [
                'domain' => 'Hydrology Longitudinal',
                'function' => 'CFPE',
                'available' => $hasCorridorContext && $fields->contains('WaterLevel'),
                'basis' => 'Available when hydrology instrumentation is tied to corridor spatial context.',
                'instrumentation' => $fields->intersect(['WaterLevel', 'WaterVelocity', 'WaterDischarge'])->values()->all(),
                'spatial_context' => $hasCorridorContext ? 'corridor' : null,
            ],
        ];
    }

    private function activeSensorMappings(MonitoringStation $station): Collection
    {
        $sensorIds = $station->sensors->pluck('id')->all();

        if (empty($sensorIds)) {
            return collect();
        }

        return SensorMappingProfile::with('canonicalParameter')
            ->whereIn('sensor_id', $sensorIds)
            ->where('status', 'active')
            ->get();
    }

    private function canonicalFields(Collection $mappings): Collection
    {
        return $mappings
            ->map(fn (SensorMappingProfile $profile) => $profile->canonicalParameter?->field_identity)
            ->filter()
            ->unique()
            ->values();
    }

    private function mqttBrokerUrl(DataLogger $logger): ?string
    {
        $mqttConfig = $logger->connectivityConfigs
            ->first(fn (ConnectivityConfig $config) => str_contains(strtolower((string) $config->protocol), 'mqtt'));

        return $mqttConfig?->host_or_endpoint ?: env('REDNODE_MQTT_BROKER_URL') ?: env('MQTT_BROKER_URL');
    }

    private function mqttTopics(DataLogger $logger): array
    {
        return $logger->connectivityConfigs
            ->pluck('topic_or_api_path')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizedState(?string $state): string
    {
        $normalized = strtolower(trim((string) $state));

        return $normalized !== '' ? $normalized : 'unknown';
    }

    private function meteorologyFields(): array
    {
        return [
            'Temperature',
            'Humidity',
            'Pressure',
            'WindSpeed',
            'WindDirection',
            'Rainfall',
            'SolarRadiation',
            'BatteryVoltage',
            'DeviceTemperature',
        ];
    }
}
