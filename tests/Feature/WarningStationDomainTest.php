<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\GeospatialWorkspace;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarningStationDomainTest extends TestCase
{
    use RefreshDatabase;

    private Role $sentinelRole;
    private Role $clientRole;
    private Client $clientA;
    private Client $clientB;
    private Project $projectA;
    private Project $projectB;
    private GeospatialWorkspace $workspaceA;
    private GeospatialWorkspace $workspaceB;

    protected function setUp(): void
    {
        parent::setUp();

        $create = Permission::create(['name' => 'project.create', 'resource' => 'project', 'action' => 'create']);
        $edit = Permission::create(['name' => 'project.edit', 'resource' => 'project', 'action' => 'edit']);
        $delete = Permission::create(['name' => 'project.delete', 'resource' => 'project', 'action' => 'delete']);

        $this->sentinelRole = Role::create(['name' => 'SentinelWarning', 'type' => 'system']);
        $this->sentinelRole->permissions()->sync([$create->id, $edit->id, $delete->id]);
        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);

        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $this->projectA = Project::create(['project_code' => 'PRJ-A', 'name' => 'Project A', 'client_id' => $this->clientA->id, 'status' => 'Active']);
        $this->projectB = Project::create(['project_code' => 'PRJ-B', 'name' => 'Project B', 'client_id' => $this->clientB->id, 'status' => 'Active']);
        $this->workspaceA = GeospatialWorkspace::create(['project_id' => $this->projectA->id, 'workspace_code' => 'GWS-A', 'name' => 'Workspace A', 'province' => 'A', 'status' => 'Normal']);
        $this->workspaceB = GeospatialWorkspace::create(['project_id' => $this->projectB->id, 'workspace_code' => 'GWS-B', 'name' => 'Workspace B', 'province' => 'B', 'status' => 'Normal']);
    }

    public function test_warning_station_belongs_to_project_scope(): void
    {
        $response = $this->actingAs($this->sentinelUser())->post(route('project-warning-stations.store'), [
            'workspace_id' => $this->workspaceA->id,
            'station_code' => 'WS-A-001',
            'name' => 'Warning A',
            'administrative_location' => 'District A',
            'coordinate' => '-0.9200, 100.3600',
            'controller_status' => 'Standby',
            'status' => 'Normal',
            'notes' => 'Registered through Sentinel Console',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warning_stations', [
            'station_code' => 'WS-A-001',
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->workspaceA->id,
            'administrative_location' => 'District A',
            'registration_status' => 'registered',
        ]);
    }

    public function test_warning_station_telemetry_configuration_reuses_mqtt_project_scope(): void
    {
        $station = $this->station($this->projectA, $this->workspaceA, 'WS-MQTT');

        $response = $this->actingAs($this->sentinelUser())->post(route('warning-station-telemetry-configs.store'), [
            'warning_station_id' => $station->id,
            'config_code' => 'WS-MQTT-IN',
            'broker_config_ref' => 'shared-mqtt-broker-a',
            'protocol' => 'MQTT',
            'host_or_endpoint' => 'mqtt://broker.local',
            'port' => 1883,
            'topic' => 'sentinel/warning/ws-mqtt/heartbeat',
            'qos' => 1,
            'retain' => false,
            'credential_ref' => 'secret-ref/ws-mqtt',
            'connection_status' => 'connected',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warning_station_telemetry_configs', [
            'project_id' => $this->projectA->id,
            'warning_station_id' => $station->id,
            'config_code' => 'WS-MQTT-IN',
            'protocol' => 'MQTT',
            'topic' => 'sentinel/warning/ws-mqtt/heartbeat',
            'qos' => 1,
            'credential_ref' => 'secret-ref/ws-mqtt',
        ]);

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('warning-stations.domain', $station))
            ->assertOk()
            ->assertJsonPath('telemetry.0.topic', 'sentinel/warning/ws-mqtt/heartbeat')
            ->assertJsonPath('telemetry.0.reuses_existing_mqtt_infrastructure', true)
            ->assertJsonMissing(['password' => 'secret']);
    }

    public function test_device_registry_tracks_expected_warning_devices(): void
    {
        $station = $this->station($this->projectA, $this->workspaceA, 'WS-DEV');
        $sentinel = $this->sentinelUser();

        foreach ([
            ['device_code' => 'WSCP-001', 'device_type' => WarningStationDevice::TYPE_WSCP],
            ['device_code' => 'ASCP-001', 'device_type' => WarningStationDevice::TYPE_ASCP],
            ['device_code' => 'SIREN-001', 'device_type' => WarningStationDevice::TYPE_SIREN],
            ['device_code' => 'BEACON-001', 'device_type' => WarningStationDevice::TYPE_BEACON],
        ] as $device) {
            $this->actingAs($sentinel)->post(route('warning-station-devices.store'), array_merge($device, [
                'warning_station_id' => $station->id,
                'name' => $device['device_code'],
                'expected' => true,
                'status' => 'registered',
            ]))->assertRedirect();
        }

        $this->assertDatabaseHas('warning_station_devices', [
            'project_id' => $this->projectA->id,
            'warning_station_id' => $station->id,
            'device_code' => 'WSCP-001',
            'device_type' => WarningStationDevice::TYPE_WSCP,
            'expected' => true,
        ]);

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('warning-stations.domain', $station))
            ->assertOk()
            ->assertJsonCount(4, 'devices')
            ->assertJsonPath('health.expected_devices', 4);
    }

    public function test_device_heartbeat_updates_availability_and_filters_secret_payload(): void
    {
        $station = $this->station($this->projectA, $this->workspaceA, 'WS-HB');
        $device = WarningStationDevice::create([
            'project_id' => $this->projectA->id,
            'warning_station_id' => $station->id,
            'device_code' => 'WSCP-HB',
            'device_type' => WarningStationDevice::TYPE_WSCP,
            'expected' => true,
            'availability_state' => 'unknown',
            'health_state' => 'unknown',
            'status' => 'registered',
        ]);

        $this->postJson(route('api.warning-stations.heartbeat', $station), [
            'device_code' => 'WSCP-HB',
            'availability_state' => 'available',
            'health_state' => 'ok',
            'health_payload' => [
                'voltage' => 12.4,
                'token' => 'must-not-persist',
            ],
        ])->assertOk()
            ->assertJsonPath('device_code', 'WSCP-HB')
            ->assertJsonPath('availability_state', 'available');

        $device->refresh();
        $station->refresh();

        $this->assertEquals('available', $device->availability_state);
        $this->assertEquals('ok', $device->health_state);
        $this->assertNotNull($device->last_heartbeat_at);
        $this->assertEquals(12.4, $device->health_payload['voltage']);
        $this->assertArrayNotHasKey('token', $device->health_payload);
        $this->assertEquals('Standby', $station->controller_status);
        $this->assertDatabaseHas('warning_station_device_heartbeats', [
            'project_id' => $this->projectA->id,
            'warning_station_id' => $station->id,
            'warning_station_device_id' => $device->id,
            'device_code' => 'WSCP-HB',
            'availability_state' => 'available',
            'health_state' => 'ok',
        ]);
    }

    public function test_unauthorized_cross_project_warning_station_access_is_blocked(): void
    {
        $stationB = $this->station($this->projectB, $this->workspaceB, 'WS-BLOCKED');

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('warning-stations.domain', $stationB))
            ->assertForbidden();
    }

    public function test_client_cannot_create_warning_station(): void
    {
        $response = $this->actingAs($this->clientUser($this->clientA))->post(route('project-warning-stations.store'), [
            'workspace_id' => $this->workspaceA->id,
            'station_code' => 'WS-CLIENT',
            'name' => 'Client Warning Station',
            'controller_status' => 'Standby',
            'status' => 'Normal',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('warning_stations', ['station_code' => 'WS-CLIENT']);
    }

    private function sentinelUser(): User
    {
        $user = User::create([
            'name' => 'Sentinel User',
            'email' => uniqid('sentinel_', true) . '@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'sentinel',
            'status' => 'active',
        ]);
        $user->assignRole($this->sentinelRole);

        return $user;
    }

    private function clientUser(Client $client): User
    {
        $user = User::create([
            'name' => 'Client User',
            'email' => uniqid('client_', true) . '@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        $user->assignRole($this->clientRole);

        return $user;
    }

    private function station(Project $project, GeospatialWorkspace $workspace, string $code): WarningStation
    {
        return WarningStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'station_code' => $code,
            'name' => $code,
            'controller_status' => 'Unknown',
            'registration_status' => 'registered',
            'registered_at' => now(),
            'status' => 'Normal',
        ]);
    }
}
