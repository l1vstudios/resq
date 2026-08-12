<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CanonicalParameter;
use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\HydrometEwsRelationship;
use App\Models\HydrometHazardClassification;
use App\Models\HydrometWdamConfig;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SentinelRuntimeReadModelTest extends TestCase
{
    use RefreshDatabase;

    private Role $clientRole;
    private Client $clientA;
    private Client $clientB;
    private Project $projectA;
    private Project $projectB;
    private GeospatialWorkspace $workspaceA;
    private GeospatialWorkspace $workspaceB;
    private CorridorMonitoring $corridorA;
    private CorridorMonitoring $corridorB;
    private MonitoringStation $stationA;
    private MonitoringStation $stationB;
    private WarningStation $warningA;
    private WarningStation $warningB;
    private DataLogger $loggerA;
    private Sensor $sensorA;
    private Sensor $sensorB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);
        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $this->projectA = Project::create(['project_code' => 'PRJ-A', 'name' => 'Project A', 'client_id' => $this->clientA->id, 'status' => 'Active']);
        $this->projectB = Project::create(['project_code' => 'PRJ-B', 'name' => 'Project B', 'client_id' => $this->clientB->id, 'status' => 'Active']);
        $this->workspaceA = GeospatialWorkspace::create(['project_id' => $this->projectA->id, 'workspace_code' => 'GWS-A', 'name' => 'Workspace A', 'province' => 'A', 'status' => 'Normal']);
        $this->workspaceB = GeospatialWorkspace::create(['project_id' => $this->projectB->id, 'workspace_code' => 'GWS-B', 'name' => 'Workspace B', 'province' => 'B', 'status' => 'Normal']);
        $this->corridorA = $this->corridor($this->projectA, $this->workspaceA, 'COR-A');
        $this->corridorB = $this->corridor($this->projectB, $this->workspaceB, 'COR-B');
        $this->stationA = $this->monitoringStation($this->projectA, $this->workspaceA, 'MS-A', $this->corridorA);
        $this->stationB = $this->monitoringStation($this->projectB, $this->workspaceB, 'MS-B', $this->corridorB);
        $this->warningA = $this->warningStation($this->projectA, $this->workspaceA, 'WS-A', $this->stationA);
        $this->warningB = $this->warningStation($this->projectB, $this->workspaceB, 'WS-B', $this->stationB);
        $this->loggerA = DataLogger::create([
            'monitoring_station_id' => $this->stationA->id,
            'logger_code' => 'DL-A',
            'logger_status' => 'Active',
        ]);
        ConnectivityConfig::create([
            'data_logger_id' => $this->loggerA->id,
            'connectivity_code' => 'CONN-A',
            'communication_type' => 'MQTT',
            'protocol' => 'MQTT',
            'topic_or_api_path' => 'sentinel/runtime/ms-a',
            'connectivity_status' => 'Online',
            'connection_state' => 'connected',
            'uplink_state' => 'uplink',
            'last_seen_at' => now(),
            'last_connected_at' => now(),
        ]);
        $this->sensorA = $this->sensor($this->stationA, 'SNS-A');
        $this->sensorB = $this->sensor($this->stationB, 'SNS-B');
    }

    public function test_latest_parameter_readings_include_timestamp_value_unit_and_freshness(): void
    {
        $old = now()->subHour();
        $latest = now()->subMinute();
        $this->reading($this->sensorA, '1.10', $old, 'Normal');
        $this->reading($this->sensorA, '2.25', $latest, 'Normal');

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('runtime.monitoring-stations.latest', [$this->stationA, 'fresh_seconds' => 300]))
            ->assertOk()
            ->assertJsonPath('station.station_code', 'MS-A')
            ->assertJsonPath('readings.0.sensor_code', 'SNS-A')
            ->assertJsonPath('readings.0.value', '2.250000')
            ->assertJsonPath('readings.0.unit', 'm')
            ->assertJsonPath('readings.0.fresh', true)
            ->assertJsonPath('readings.0.data_freshness', 'fresh');
    }

    public function test_latest_readings_expand_modbus_multi_parameter_registers(): void
    {
        $temperature = CanonicalParameter::firstOrCreate(
            ['field_identity' => 'Temperature'],
            ['domain' => 'meteorology', 'canonical_unit' => 'C']
        );
        $humidity = CanonicalParameter::firstOrCreate(
            ['field_identity' => 'Humidity'],
            ['domain' => 'meteorology', 'canonical_unit' => '%RH']
        );

        SensorMappingProfile::create([
            'sensor_id' => $this->sensorA->id,
            'profile_code' => 'MAP-TEMP',
            'source_parameter' => 'Temperature',
            'source_unit' => 'C',
            'register_address' => '40002',
            'value_type' => 'uint16',
            'data_length' => 1,
            'canonical_parameter_id' => $temperature->id,
            'status' => 'active',
        ]);
        SensorMappingProfile::create([
            'sensor_id' => $this->sensorA->id,
            'profile_code' => 'MAP-HUM',
            'source_parameter' => 'Humidity',
            'source_unit' => '%RH',
            'register_address' => '40003',
            'value_type' => 'uint16',
            'data_length' => 1,
            'canonical_parameter_id' => $humidity->id,
            'status' => 'active',
        ]);

        TelemetryReading::create([
            'sensor_id' => $this->sensorA->id,
            'data_logger_id' => $this->sensorA->data_logger_id,
            'value' => 'Temperature 25.00 C, Humidity 80.00 %RH',
            'numeric_value' => 25,
            'registers' => [0, 25, 80],
            'parameter_values' => [],
            'alert_level' => 'Normal',
            'status' => 'Normal',
            'received_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('runtime.monitoring-stations.latest', [$this->stationA, 'fresh_seconds' => 300]))
            ->assertOk()
            ->assertJsonCount(2, 'readings')
            ->assertJsonPath('readings.0.parameter', 'Temperature')
            ->assertJsonPath('readings.0.value', 25)
            ->assertJsonPath('readings.1.parameter', 'Humidity')
            ->assertJsonPath('readings.1.value', 80);
    }

    public function test_time_series_is_bounded_and_scoped_to_station_sensor(): void
    {
        $this->reading($this->sensorA, '1.00', now()->subMinutes(30), 'Normal');
        $this->reading($this->sensorA, '1.50', now()->subMinutes(20), 'Normal');
        $this->reading($this->sensorA, '2.00', now()->subMinutes(10), 'Waspada');
        $this->reading($this->sensorB, '9.99', now()->subMinutes(10), 'Awas');

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('runtime.monitoring-stations.time-series', [
                $this->stationA,
                'from' => now()->subHour()->toISOString(),
                'to' => now()->toISOString(),
                'limit' => 2,
            ]))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('readings.0.sensor_code', 'SNS-A');

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('runtime.monitoring-stations.time-series', [
                $this->stationA,
                'sensor_id' => $this->sensorB->id,
            ]))
            ->assertForbidden();
    }

    public function test_station_health_read_model_reports_integrity_components_and_unresolved_thresholds(): void
    {
        $this->reading($this->sensorA, '2.25', now()->subMinute(), 'Normal');

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('runtime.monitoring-stations.show', [$this->stationA, 'fresh_seconds' => 300]))
            ->assertOk()
            ->assertJsonPath('integrity.components.connectivity.status', 'Healthy')
            ->assertJsonPath('integrity.components.telemetry_data_health.status', 'Healthy')
            ->assertJsonPath('integrity.components.power_health.status', 'Warning')
            ->assertJsonPath('integrity.components.calibration.status', 'Warning')
            ->assertJsonPath('analytical_outputs.0.function', 'TDE')
            ->assertJsonPath('analytical_outputs.0.execution_state', 'not_implemented')
            ->assertJsonPath('unresolved_runtime_rules.1', 'Power-health thresholds are not defined in the current station/device domain.');
    }

    public function test_project_runtime_exposes_hazard_warning_and_administrative_state_by_scope(): void
    {
        $this->stationA->update([
            'service_status' => 'Active',
            'service_period_start' => '2026-01-01',
            'service_period_end' => '2026-12-31',
            'entitlement' => 'standard',
            'package_status' => 'Active',
            'administrative_attention' => 'Renew calibration certificate',
        ]);
        $this->reading($this->sensorA, '3.20', now()->subMinute(), 'Siaga');
        $relationship = HydrometEwsRelationship::create([
            'project_id' => $this->projectA->id,
            'corridor_id' => $this->corridorA->id,
            'monitoring_station_id' => $this->stationA->id,
            'warning_station_id' => $this->warningA->id,
            'relationship_code' => 'EWS-A',
            'name' => 'EWS A',
            'status' => 'active',
        ]);
        HydrometHazardClassification::create([
            'project_id' => $this->projectA->id,
            'hydromet_ews_relationship_id' => $relationship->id,
            'corridor_id' => $this->corridorA->id,
            'monitoring_station_id' => $this->stationA->id,
            'sensor_id' => $this->sensorA->id,
            'classification_code' => 'HZ-A',
            'parameter' => 'WaterLevel',
            'reading_method' => 'Absolute',
            'hazard_levels' => HydrometHazardClassification::defaultHazardLevels(),
            'evaluation_engine' => 'configuration_only',
            'status' => 'draft',
        ]);
        HydrometWdamConfig::create([
            'project_id' => $this->projectA->id,
            'hydromet_ews_relationship_id' => $relationship->id,
            'warning_station_id' => $this->warningA->id,
            'wdam_code' => 'WDAM-A',
            'warning_station_assignment_enabled' => true,
            'automatic_activation_enabled' => true,
            'authority_method' => 'authorized_operator_approval',
        ]);
        WarningStationDevice::create([
            'project_id' => $this->projectA->id,
            'warning_station_id' => $this->warningA->id,
            'device_code' => 'SIREN-A',
            'device_type' => WarningStationDevice::TYPE_SIREN,
            'expected' => true,
            'availability_state' => 'available',
            'health_state' => 'ok',
            'status' => 'registered',
        ]);

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('runtime.projects.show', [$this->projectA, 'fresh_seconds' => 300]))
            ->assertOk()
            ->assertJsonPath('stations.0.hazard.state', 'SIAGA')
            ->assertJsonPath('corridors.0.hazard_state', 'SIAGA')
            ->assertJsonPath('stations.0.warning_state.activation_status', 'automatic_configured')
            ->assertJsonPath('warning_stations.0.station_code', 'WS-A')
            ->assertJsonPath('warning_stations.0.available_devices', 1)
            ->assertJsonPath('stations.0.administrative.registration_status', 'registered')
            ->assertJsonPath('stations.0.administrative.service_period.start', '2026-01-01')
            ->assertJsonPath('stations.0.administrative.entitlement', 'standard')
            ->assertJsonPath('stations.0.administrative.administrative_attention', 'Renew calibration certificate');
    }

    public function test_cross_project_runtime_access_is_blocked(): void
    {
        $clientA = $this->clientUser($this->clientA);

        $this->actingAs($clientA)
            ->getJson(route('runtime.projects.show', $this->projectB))
            ->assertForbidden();

        $this->actingAs($clientA)
            ->getJson(route('runtime.monitoring-stations.latest', $this->stationB))
            ->assertForbidden();
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

    private function corridor(Project $project, GeospatialWorkspace $workspace, string $code): CorridorMonitoring
    {
        return CorridorMonitoring::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_code' => $code,
            'name' => $code,
            'status' => 'Active',
        ]);
    }

    private function monitoringStation(
        Project $project,
        GeospatialWorkspace $workspace,
        string $code,
        CorridorMonitoring $corridor
    ): MonitoringStation {
        return MonitoringStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_id' => $corridor->id,
            'station_code' => $code,
            'name' => $code,
            'station_type' => 'hydromet_monitoring',
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'registration_status' => 'registered',
            'status' => 'Normal',
        ]);
    }

    private function warningStation(
        Project $project,
        GeospatialWorkspace $workspace,
        string $code,
        MonitoringStation $monitoringStation
    ): WarningStation {
        return WarningStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'monitoring_station_id' => $monitoringStation->id,
            'station_code' => $code,
            'name' => $code,
            'controller_status' => 'Standby',
            'registration_status' => 'registered',
            'status' => 'Normal',
        ]);
    }

    private function sensor(MonitoringStation $station, string $code): Sensor
    {
        $prefix = MstPrefix::firstOrCreate(
            ['prefix_code' => 'PFX'],
            ['name' => 'Default Prefix', 'status' => 'Active']
        );

        return Sensor::create([
            'workspace_id' => $station->workspace_id,
            'monitoring_station_id' => $station->id,
            'data_logger_id' => $station->id === $this->stationA?->id ? $this->loggerA?->id : null,
            'mst_prefix_id' => $prefix->id,
            'slave_id' => '1',
            'address' => (string) Sensor::count(),
            'function_code' => 'FC03',
            'quantity' => 1,
            'poll_interval_ms' => 1000,
            'sensor_code' => $code,
            'type' => 'water_level',
            'parameter' => 'WaterLevel',
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => 'm',
            'reading_method' => 'Absolute',
            'alert_level' => 'Normal',
            'status' => 'Normal',
        ]);
    }

    private function reading(Sensor $sensor, string $value, mixed $receivedAt, string $level): TelemetryReading
    {
        $reading = TelemetryReading::create([
            'sensor_id' => $sensor->id,
            'data_logger_id' => $sensor->data_logger_id,
            'value' => $value,
            'numeric_value' => $value,
            'alert_level' => $level,
            'status' => $level,
            'received_at' => $receivedAt,
        ]);
        $sensor->update([
            'value' => $value,
            'alert_level' => $level,
            'status' => $level,
            'last_seen_at' => $receivedAt,
        ]);

        return $reading;
    }
}
