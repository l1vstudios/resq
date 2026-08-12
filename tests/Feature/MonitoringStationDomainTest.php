<?php

namespace Tests\Feature;

use App\Models\CanonicalParameter;
use App\Models\Client;
use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\User;
use App\Services\MonitoringStationDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringStationDomainTest extends TestCase
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
    private MstPrefix $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $create = Permission::create(['name' => 'project.create', 'resource' => 'project', 'action' => 'create']);
        $edit = Permission::create(['name' => 'project.edit', 'resource' => 'project', 'action' => 'edit']);
        $delete = Permission::create(['name' => 'project.delete', 'resource' => 'project', 'action' => 'delete']);

        $this->sentinelRole = Role::create(['name' => 'SentinelMonitoring', 'type' => 'system']);
        $this->sentinelRole->permissions()->sync([$create->id, $edit->id, $delete->id]);
        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);

        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $this->projectA = Project::create(['project_code' => 'PRJ-A', 'name' => 'Project A', 'client_id' => $this->clientA->id, 'status' => 'Active']);
        $this->projectB = Project::create(['project_code' => 'PRJ-B', 'name' => 'Project B', 'client_id' => $this->clientB->id, 'status' => 'Active']);
        $this->workspaceA = GeospatialWorkspace::create(['project_id' => $this->projectA->id, 'workspace_code' => 'GWS-A', 'name' => 'Workspace A', 'province' => 'A', 'status' => 'Normal']);
        $this->workspaceB = GeospatialWorkspace::create(['project_id' => $this->projectB->id, 'workspace_code' => 'GWS-B', 'name' => 'Workspace B', 'province' => 'B', 'status' => 'Normal']);
        $this->prefix = MstPrefix::create(['prefix_code' => 'PFX', 'name' => 'Default Prefix', 'status' => 'Active']);
    }

    public function test_station_belongs_to_correct_project(): void
    {
        $sentinel = $this->sentinelUser();

        $response = $this->actingAs($sentinel)->post(route('project-monitoring-stations.store'), [
            'workspace_id' => $this->workspaceA->id,
            'station_code' => 'MS-A-001',
            'name' => 'Monitoring A',
            'station_type' => 'hydromet_monitoring',
            'coordinate' => '-0.9200, 100.3600',
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'status' => 'Normal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('monitoring_stations', [
            'station_code' => 'MS-A-001',
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->workspaceA->id,
            'station_type' => 'hydromet_monitoring',
        ]);
    }

    public function test_telemetry_configuration_is_project_scoped(): void
    {
        $stationA = $this->station($this->projectA, $this->workspaceA, 'MS-A');
        $stationB = $this->station($this->projectB, $this->workspaceB, 'MS-B');
        $logger = DataLogger::create([
            'monitoring_station_id' => $stationA->id,
            'logger_code' => 'DL-A',
            'logger_status' => 'Active',
        ]);
        ConnectivityConfig::create([
            'data_logger_id' => $logger->id,
            'connectivity_code' => 'MQTT-A',
            'communication_type' => 'MQTT',
            'protocol' => 'MQTT',
            'host_or_endpoint' => 'mqtt://broker.local',
            'topic_or_api_path' => 'resq/telemetry/ms-a',
            'connectivity_status' => 'Online',
            'connection_state' => 'connected',
            'uplink_state' => 'uplink',
            'last_seen_at' => now(),
        ]);

        $clientAUser = $this->clientUser($this->clientA);

        $this->actingAs($clientAUser)
            ->getJson(route('monitoring-stations.domain', $stationA))
            ->assertOk()
            ->assertJsonPath('station.project_id', $this->projectA->id)
            ->assertJsonPath('telemetry.0.mqtt.topics.0', 'resq/telemetry/ms-a');

        $this->actingAs($clientAUser)
            ->getJson(route('monitoring-stations.domain', $stationB))
            ->assertForbidden();
    }

    public function test_sensors_belong_to_correct_station(): void
    {
        $sentinel = $this->sentinelUser();
        $stationA = $this->station($this->projectA, $this->workspaceA, 'MS-A');
        $stationB = $this->station($this->projectB, $this->workspaceB, 'MS-B');

        $response = $this->actingAs($sentinel)->post(route('project-sensors.store'), $this->sensorPayload([
            'workspace_id' => $this->workspaceA->id,
            'monitoring_station_id' => $stationB->id,
            'sensor_code' => 'SNS-WRONG-STATION',
        ]));

        $response->assertForbidden();
        $this->assertDatabaseMissing('sensors', ['sensor_code' => 'SNS-WRONG-STATION']);

        $validResponse = $this->actingAs($sentinel)->post(route('project-sensors.store'), $this->sensorPayload([
            'workspace_id' => $this->workspaceA->id,
            'monitoring_station_id' => $stationA->id,
            'sensor_code' => 'SNS-A',
        ]));

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('sensors', [
            'sensor_code' => 'SNS-A',
            'workspace_id' => $this->workspaceA->id,
            'monitoring_station_id' => $stationA->id,
        ]);
    }

    public function test_sensor_registration_can_create_valid_sentinel_parameter_mapping(): void
    {
        $sentinel = $this->sentinelUser();
        $station = $this->station($this->projectA, $this->workspaceA, 'MS-MAP');
        $parameter = CanonicalParameter::firstOrCreate(
            ['field_identity' => 'Rainfall'],
            [
                'domain' => 'meteorology',
                'canonical_unit' => 'mm',
                'status' => 'active',
            ]
        );

        $response = $this->actingAs($sentinel)->post(route('project-sensors.store'), $this->sensorPayload([
            'workspace_id' => $this->workspaceA->id,
            'monitoring_station_id' => $station->id,
            'sensor_code' => 'SNS-RAIN',
            'type' => 'tipping_bucket',
            'canonical_parameter_id' => $parameter->id,
            'source_parameter' => 'rain_mm',
        ]));

        $response->assertRedirect();
        $sensor = Sensor::where('sensor_code', 'SNS-RAIN')->firstOrFail();
        $this->assertDatabaseHas('sensor_mapping_profiles', [
            'sensor_id' => $sensor->id,
            'canonical_parameter_id' => $parameter->id,
            'source_parameter' => 'rain_mm',
            'status' => 'active',
        ]);
    }

    public function test_client_cannot_create_monitoring_station(): void
    {
        $clientUser = $this->clientUser($this->clientA);

        $response = $this->actingAs($clientUser)->post(route('project-monitoring-stations.store'), [
            'workspace_id' => $this->workspaceA->id,
            'station_code' => 'MS-CLIENT',
            'name' => 'Client Station',
            'station_type' => 'hydromet_monitoring',
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'status' => 'Normal',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('monitoring_stations', ['station_code' => 'MS-CLIENT']);
    }

    public function test_station_capability_is_derived_from_instrumentation_and_corridor_context(): void
    {
        $corridor = CorridorMonitoring::create([
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->workspaceA->id,
            'corridor_code' => 'COR-A',
            'name' => 'Corridor A',
            'status' => 'Active',
        ]);
        $station = $this->station($this->projectA, $this->workspaceA, 'MS-CAP', ['corridor_id' => $corridor->id]);
        $rain = $this->canonical('Rainfall', 'meteorology');
        $level = $this->canonical('WaterLevel', 'hydrology');
        $velocity = $this->canonical('WaterVelocity', 'hydrology');
        $rainSensor = $this->sensor($station, 'SNS-RAIN', 'rain_gauge');
        $levelSensor = $this->sensor($station, 'SNS-LEVEL', 'water_level');
        $velocitySensor = $this->sensor($station, 'SNS-VEL', 'velocity_meter');
        $this->map($rainSensor, $rain);
        $this->map($levelSensor, $level);
        $this->map($velocitySensor, $velocity);

        $capabilities = app(MonitoringStationDomainService::class)
            ->stationDomain($station)['capabilities'];

        $this->assertTrue(collect($capabilities)->firstWhere('function', 'TDE')['available']);
        $this->assertTrue(collect($capabilities)->firstWhere('function', 'Discharge')['available']);
        $this->assertTrue(collect($capabilities)->firstWhere('function', 'CFPE')['available']);
    }

    private function sensorPayload(array $overrides = []): array
    {
        return array_merge([
            'workspace_id' => $this->workspaceA->id,
            'monitoring_station_id' => null,
            'mst_prefix_id' => $this->prefix->id,
            'slave_id' => '1',
            'address' => '0',
            'function_code' => 'FC03',
            'quantity' => 1,
            'poll_interval_ms' => 1000,
            'sensor_code' => 'SNS-' . uniqid(),
            'type' => 'water_level',
            'parameter' => 'Water Level',
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => 'm',
            'reading_method' => 'Absolute',
            'alert_level' => 'Normal',
            'status' => 'Normal',
        ], $overrides);
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

    private function station(Project $project, GeospatialWorkspace $workspace, string $code, array $overrides = []): MonitoringStation
    {
        return MonitoringStation::create(array_merge([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'station_code' => $code,
            'name' => $code,
            'station_type' => 'hydromet_monitoring',
            'registration_status' => 'registered',
            'connectivity_status' => 'Online',
            'logger_status' => 'Active',
            'status' => 'Normal',
        ], $overrides));
    }

    private function sensor(MonitoringStation $station, string $code, string $type): Sensor
    {
        return Sensor::create([
            'workspace_id' => $station->workspace_id,
            'monitoring_station_id' => $station->id,
            'mst_prefix_id' => $this->prefix->id,
            'slave_id' => '1',
            'address' => (string) Sensor::count(),
            'function_code' => 'FC03',
            'quantity' => 1,
            'poll_interval_ms' => 1000,
            'sensor_code' => $code,
            'type' => $type,
            'parameter' => $type,
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => 'm',
            'reading_method' => 'Absolute',
            'alert_level' => 'Normal',
            'status' => 'Normal',
        ]);
    }

    private function canonical(string $field, string $domain): CanonicalParameter
    {
        return CanonicalParameter::firstOrCreate(
            ['field_identity' => $field],
            [
                'domain' => $domain,
                'canonical_unit' => 'm',
                'status' => 'active',
            ]
        );
    }

    private function map(Sensor $sensor, CanonicalParameter $parameter): SensorMappingProfile
    {
        return SensorMappingProfile::create([
            'sensor_id' => $sensor->id,
            'profile_code' => 'MAP-' . $sensor->sensor_code . '-' . $parameter->field_identity,
            'source_parameter' => $parameter->field_identity,
            'canonical_parameter_id' => $parameter->id,
            'value_origin' => 'direct_measurement',
            'status' => 'active',
        ]);
    }
}
