<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\HydrometEwsRelationship;
use App\Models\HydrometWdamConfig;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Models\WarningStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformOperationsUiTest extends TestCase
{
    use RefreshDatabase;

    private Role $operatorRole;
    private Role $integrityOnlyRole;
    private Role $clientRole;
    private Client $client;
    private Project $project;
    private GeospatialWorkspace $workspace;
    private CorridorMonitoring $corridor;
    private MonitoringStation $station;

    protected function setUp(): void
    {
        parent::setUp();

        $state = Permission::create(['name' => 'operational-state.access', 'resource' => 'operational-state', 'action' => 'access']);
        $integrity = Permission::create(['name' => 'operational-integrity.access', 'resource' => 'operational-integrity', 'action' => 'access']);
        $administrative = Permission::create(['name' => 'administrative-monitoring.access', 'resource' => 'administrative-monitoring', 'action' => 'access']);

        $this->operatorRole = Role::create(['name' => 'PlatformOperator', 'type' => 'system']);
        $this->operatorRole->permissions()->sync([$state->id, $integrity->id, $administrative->id]);
        $this->integrityOnlyRole = Role::create(['name' => 'IntegrityOnly', 'type' => 'system']);
        $this->integrityOnlyRole->permissions()->sync([$integrity->id]);
        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);

        $this->client = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->project = Project::create(['project_code' => 'PRJ-A', 'name' => 'Project A', 'client_id' => $this->client->id, 'status' => 'Active']);
        $this->workspace = GeospatialWorkspace::create([
            'project_id' => $this->project->id,
            'workspace_code' => 'GWS-A',
            'name' => 'Workspace A',
            'province' => 'A',
            'city' => 'City A',
            'latitude' => -0.92,
            'longitude' => 100.36,
            'status' => 'Normal',
        ]);
        $this->corridor = CorridorMonitoring::create([
            'project_id' => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'corridor_code' => 'COR-A',
            'name' => 'Corridor A',
            'status' => 'Active',
        ]);
        $this->station = MonitoringStation::create([
            'project_id' => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'corridor_id' => $this->corridor->id,
            'station_code' => 'MS-A',
            'name' => 'Monitoring A',
            'station_type' => 'hydromet_monitoring',
            'latitude' => -0.91,
            'longitude' => 100.37,
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'registration_status' => 'registered',
            'service_status' => 'Active',
            'service_period_start' => '2026-01-01',
            'service_period_end' => now()->addDays(30)->toDateString(),
            'entitlement' => 'standard',
            'package_status' => 'Active',
            'administrative_attention' => 'Check calibration record',
            'status' => 'Normal',
        ]);
        $logger = DataLogger::create(['monitoring_station_id' => $this->station->id, 'logger_code' => 'DL-A', 'logger_status' => 'Active']);
        ConnectivityConfig::create([
            'data_logger_id' => $logger->id,
            'connectivity_code' => 'CONN-A',
            'connectivity_status' => 'Online',
            'connection_state' => 'connected',
            'uplink_state' => 'uplink',
            'last_seen_at' => now(),
        ]);
        $prefix = MstPrefix::create(['prefix_code' => 'PFX', 'name' => 'Prefix', 'status' => 'Active']);
        $sensor = Sensor::create([
            'workspace_id' => $this->workspace->id,
            'monitoring_station_id' => $this->station->id,
            'data_logger_id' => $logger->id,
            'mst_prefix_id' => $prefix->id,
            'slave_id' => '1',
            'address' => '0',
            'function_code' => 'FC03',
            'quantity' => 1,
            'poll_interval_ms' => 1000,
            'sensor_code' => 'SNS-A',
            'type' => 'water_level',
            'parameter' => 'WaterLevel',
            'value' => '2.20',
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => 'm',
            'reading_method' => 'Absolute',
            'alert_level' => 'Waspada',
            'status' => 'Waspada',
            'last_seen_at' => now(),
        ]);
        TelemetryReading::create([
            'sensor_id' => $sensor->id,
            'data_logger_id' => $logger->id,
            'value' => '2.20',
            'numeric_value' => '2.20',
            'alert_level' => 'Waspada',
            'status' => 'Waspada',
            'received_at' => now(),
        ]);
        $warning = WarningStation::create([
            'project_id' => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'monitoring_station_id' => $this->station->id,
            'station_code' => 'WS-A',
            'name' => 'Warning A',
            'controller_status' => 'Standby',
            'registration_status' => 'registered',
            'status' => 'Normal',
        ]);
        $relationship = HydrometEwsRelationship::create([
            'project_id' => $this->project->id,
            'corridor_id' => $this->corridor->id,
            'monitoring_station_id' => $this->station->id,
            'warning_station_id' => $warning->id,
            'relationship_code' => 'EWS-A',
            'name' => 'EWS A',
            'status' => 'active',
        ]);
        HydrometWdamConfig::create([
            'project_id' => $this->project->id,
            'hydromet_ews_relationship_id' => $relationship->id,
            'warning_station_id' => $warning->id,
            'wdam_code' => 'WDAM-A',
            'warning_station_assignment_enabled' => true,
            'automatic_activation_enabled' => true,
        ]);
    }

    public function test_platform_operations_navigation_pages_render_for_operator(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('platform-operations.index'))
            ->assertOk()
            ->assertSee('Project Distribution')
            ->assertSee('PRJ-A')
            ->assertDontSee('Reporting');

        $this->actingAs($operator)->get(route('platform-operations.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Spatial Operational Map')
            ->assertSee('Corridor List');

        $this->actingAs($operator)->get(route('platform-operations.corridors.show', [$this->project, $this->corridor]))
            ->assertOk()
            ->assertSee('Current Data Preview')
            ->assertSee('Short Time-Series Preview');
    }

    public function test_platform_operations_layout_uses_absolute_asset_urls(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('platform-operations.integrity.index'))
            ->assertOk()
            ->assertSee('href="https://dev.sentinelplatform.id/build/css/bootstrap.min.css"', false)
            ->assertSee('href="https://dev.sentinelplatform.id/build/css/app.min.css"', false)
            ->assertSee('src="https://dev.sentinelplatform.id/build/js/app.js"', false);
    }

    public function test_shared_station_ui_switches_tabs_without_duplicate_station_pages(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('platform-operations.stations.show', [$this->station, 'tab' => 'state']))
            ->assertOk()
            ->assertSee('Operational State')
            ->assertSee('Current Data')
            ->assertSee('Functional / Analytical Output')
            ->assertSee('href="#station-context"', false)
            ->assertSee('href="#current-data"', false)
            ->assertSee('tab=integrity', false)
            ->assertSee('tab=administrative', false);

        $this->actingAs($operator)->get(route('platform-operations.stations.show', [$this->station, 'tab' => 'integrity']))
            ->assertOk()
            ->assertSee('Operational Integrity')
            ->assertSee('Telemetry Data Health');

        $this->actingAs($operator)->get(route('platform-operations.stations.show', [$this->station, 'tab' => 'administrative']))
            ->assertOk()
            ->assertSee('Administrative Monitoring')
            ->assertSee('Check calibration record');
    }

    public function test_integrity_and_administrative_landings_filter_and_link_to_shared_station_ui(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('platform-operations.integrity.index', ['status' => 'Warning']))
            ->assertOk()
            ->assertSee('All Station Integrity')
            ->assertSee('MS-A')
            ->assertSee('tab=integrity', false);

        $this->actingAs($operator)->get(route('platform-operations.administrative.index', ['status' => 'Attention']))
            ->assertOk()
            ->assertSee('All Station Administrative Monitoring')
            ->assertSee('MS-A')
            ->assertSee('tab=administrative', false);
    }

    public function test_permissions_block_client_and_wrong_perspective_access(): void
    {
        $client = User::create([
            'name' => 'Client User',
            'email' => 'client-ui@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $this->client->id,
            'status' => 'active',
        ]);
        $client->assignRole($this->clientRole);

        $this->actingAs($client)->get(route('platform-operations.index'))->assertForbidden();

        $integrityOnly = User::create([
            'name' => 'Integrity Only',
            'email' => 'integrity@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'sentinel',
            'status' => 'active',
        ]);
        $integrityOnly->assignRole($this->integrityOnlyRole);

        $this->actingAs($integrityOnly)->get(route('platform-operations.integrity.index'))->assertOk();
        $this->actingAs($integrityOnly)->get(route('platform-operations.index'))->assertForbidden();
    }

    private function operator(): User
    {
        $user = User::create([
            'name' => 'Platform Operator',
            'email' => uniqid('operator_', true) . '@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'sentinel',
            'status' => 'active',
        ]);
        $user->assignRole($this->operatorRole);

        return $user;
    }
}
