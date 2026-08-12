<?php

namespace App\Services;

use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use App\Models\WarningStationDeviceHeartbeat;
use App\Models\WarningStationTelemetryConfig;
use Illuminate\Support\Carbon;

class WarningStationDomainService
{
    public function stationDomain(WarningStation $station): array
    {
        $station->loadMissing([
            'project',
            'workspace.project',
            'monitoringStation',
            'telemetryConfigs',
            'devices.heartbeats',
        ]);

        return [
            'station' => $this->stationRegistry($station),
            'telemetry' => $this->telemetry($station),
            'devices' => $this->devices($station),
            'health' => $this->health($station),
            'command_interface' => $this->commandInterface($station),
        ];
    }

    public function stationProjectId(WarningStation $station): ?int
    {
        return $station->project_id ?: $station->workspace?->project_id;
    }

    public function recordHeartbeat(WarningStation $station, array $data): WarningStationDeviceHeartbeat
    {
        $projectId = $this->stationProjectId($station);
        $device = WarningStationDevice::where('warning_station_id', $station->id)
            ->where('device_code', $data['device_code'])
            ->first();
        $receivedAt = now();

        $heartbeat = WarningStationDeviceHeartbeat::create([
            'project_id' => $projectId,
            'warning_station_id' => $station->id,
            'warning_station_device_id' => $device?->id,
            'device_code' => $data['device_code'],
            'device_type' => $data['device_type'] ?? $device?->device_type,
            'availability_state' => $data['availability_state'] ?? 'available',
            'health_state' => $data['health_state'] ?? 'ok',
            'observed_at' => ! empty($data['observed_at']) ? Carbon::parse($data['observed_at']) : $receivedAt,
            'received_at' => $receivedAt,
            'health_payload' => $this->filteredHealthPayload($data['health_payload'] ?? []),
        ]);

        if ($device) {
            $device->update([
                'availability_state' => $heartbeat->availability_state,
                'health_state' => $heartbeat->health_state,
                'last_heartbeat_at' => $heartbeat->received_at,
                'health_payload' => $heartbeat->health_payload,
            ]);
        }

        $station->update([
            'controller_status' => $this->stationControllerStatus($station->fresh('devices')),
        ]);

        return $heartbeat;
    }

    public function stationRegistry(WarningStation $station): array
    {
        return [
            'id' => $station->id,
            'project_id' => $this->stationProjectId($station),
            'project_code' => $station->project?->project_code ?? $station->workspace?->project?->project_code,
            'workspace_id' => $station->workspace_id,
            'workspace_code' => $station->workspace?->workspace_code,
            'monitoring_station_id' => $station->monitoring_station_id,
            'monitoring_station_code' => $station->monitoringStation?->station_code,
            'station_code' => $station->station_code,
            'name' => $station->name,
            'zone_id' => $station->zone_id,
            'administrative_location' => $station->administrative_location,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'controller_id' => $station->controller_id,
            'controller_model' => $station->controller_model,
            'controller_vendor' => $station->controller_vendor,
            'controller_status' => $station->controller_status,
            'registration_status' => $station->registration_status,
            'registered_at' => optional($station->registered_at)->toISOString(),
            'registered_by_user_id' => $station->registered_by_user_id,
            'status' => $station->status,
            'notes' => $station->notes,
        ];
    }

    public function telemetry(WarningStation $station): array
    {
        return $station->telemetryConfigs
            ->map(fn (WarningStationTelemetryConfig $config) => [
                'id' => $config->id,
                'config_code' => $config->config_code,
                'broker_config_ref' => $config->broker_config_ref,
                'protocol' => $config->protocol,
                'host_or_endpoint' => $config->host_or_endpoint,
                'port' => $config->port,
                'topic' => $config->topic,
                'qos' => $config->qos,
                'retain' => $config->retain,
                'credential_ref' => $config->credential_ref,
                'connection_status' => $config->connection_status,
                'last_connected_at' => optional($config->last_connected_at)->toISOString(),
                'last_seen_at' => optional($config->last_seen_at)->toISOString(),
                'last_error' => $config->last_error,
                'reuses_existing_mqtt_infrastructure' => true,
            ])
            ->values()
            ->all();
    }

    public function devices(WarningStation $station): array
    {
        return $station->devices
            ->map(fn (WarningStationDevice $device) => [
                'id' => $device->id,
                'device_code' => $device->device_code,
                'device_type' => $device->device_type,
                'name' => $device->name,
                'vendor' => $device->vendor,
                'model' => $device->model,
                'serial_number' => $device->serial_number,
                'expected' => $device->expected,
                'availability_state' => $device->availability_state,
                'health_state' => $device->health_state,
                'last_heartbeat_at' => optional($device->last_heartbeat_at)->toISOString(),
                'status' => $device->status,
                'notes' => $device->notes,
            ])
            ->values()
            ->all();
    }

    public function health(WarningStation $station): array
    {
        $expected = $station->devices->where('expected', true);
        $available = $expected->where('availability_state', 'available');
        $healthy = $expected->where('health_state', 'ok');

        return [
            'expected_devices' => $expected->count(),
            'available_devices' => $available->count(),
            'healthy_devices' => $healthy->count(),
            'availability_state' => $expected->isNotEmpty() && $available->count() === $expected->count()
                ? 'available'
                : ($available->isNotEmpty() ? 'partial' : 'unavailable'),
            'health_state' => $expected->isNotEmpty() && $healthy->count() === $expected->count()
                ? 'ok'
                : ($healthy->isNotEmpty() ? 'degraded' : 'unknown'),
            'last_heartbeat_at' => optional($station->devices->sortByDesc('last_heartbeat_at')->first()?->last_heartbeat_at)->toISOString(),
        ];
    }

    public function commandInterface(WarningStation $station): array
    {
        return [
            'prepared' => true,
            'execution_enabled' => false,
            'flow' => [
                'Sentinel Backend',
                'Warning Command/Payload',
                'WSCP',
                'ASCP',
                'Configured Output Package',
            ],
            'boundary' => 'Hazard-triggered execution and low-level ASCP output behavior are outside Phase 4.',
            'target_topic' => $station->telemetryConfigs->first()?->topic,
        ];
    }

    private function stationControllerStatus(WarningStation $station): string
    {
        $health = $this->health($station);

        return match ($health['health_state']) {
            'ok' => 'Standby',
            'degraded' => 'Degraded',
            default => 'Unknown',
        };
    }

    private function filteredHealthPayload(array $payload): array
    {
        return collect($payload)
            ->except([
                'password',
                'secret',
                'token',
                'mqtt_password',
                'credential',
                'private_key',
            ])
            ->all();
    }
}
