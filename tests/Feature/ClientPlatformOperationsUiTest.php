<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPlatformOperationsUiTest extends TestCase
{
    use RefreshDatabase;

    private Role $clientRole;

    private Client $clientA;

    private Client $clientB;

    private Project $projectA;

    private Project $projectB;

    private CorridorMonitoring $corridorA;

    private CorridorMonitoring $corridorB;

    private MonitoringStation $stationA;

    private MonitoringStation $stationB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);
        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);

        [$this->projectA, $this->corridorA, $this->stationA] = $this->projectStack($this->clientA, 'A');
        [$this->projectB, $this->corridorB, $this->stationB] = $this->projectStack($this->clientB, 'B');
    }

    public function test_client_operations_start_from_authorized_project_not_national_overview(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->get(route('client-operations.index'))
            ->assertRedirect(route('client-operations.projects.show', $this->projectA));

        $this->actingAs($client)->get(route('client-operations.projects.show', $this->projectA))
            ->assertOk()
            ->assertSee('Client Operations')
            ->assertSee('Spatial Operational Map')
            ->assertSee('Corridor List')
            ->assertSee('COR-A')
            ->assertDontSee('Project Distribution')
            ->assertDontSee('National')
            ->assertDontSee('Registered&lt;/span&gt;', false);
    }

    public function test_client_reuses_project_corridor_and_shared_station_ui(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->get(route('client-operations.corridors.show', [$this->projectA, $this->corridorA]))
            ->assertOk()
            ->assertSee('Corridor Spatial Map')
            ->assertSee('Current Data Preview')
            ->assertSee(route('client-operations.stations.show', $this->stationA), false);

        $this->actingAs($client)->get(route('client-operations.stations.show', [$this->stationA, 'tab' => 'state']))
            ->assertOk()
            ->assertSee('Operational State')
            ->assertSee('Current Data')
            ->assertSee('Functional / Analytical Output')
            ->assertSee('Function Configuration')
            ->assertSee('Reporting &amp; Export', false)
            ->assertDontSee('Report Builder');
    }

    public function test_client_integrity_and_administrative_flows_use_project_station_list(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->get(route('client-operations.index', ['tab' => 'integrity']))
            ->assertRedirect(route('client-operations.projects.stations', [$this->projectA, 'tab' => 'integrity']));

        $this->actingAs($client)->get(route('client-operations.projects.stations', [$this->projectA, 'tab' => 'integrity', 'status' => 'Warning']))
            ->assertOk()
            ->assertSee('Project Station Integrity')
            ->assertSee('MS-A')
            ->assertSee('tab=integrity', false);

        $this->actingAs($client)->get(route('client-operations.projects.stations', [$this->projectA, 'tab' => 'administrative', 'status' => 'Attention']))
            ->assertOk()
            ->assertSee('Project Station Administrative Monitoring')
            ->assertSee('MS-A')
            ->assertSee('Calibration review')
            ->assertSee('tab=administrative', false);
    }

    public function test_client_direct_url_access_is_project_scoped(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->get(route('platform-operations.index'))->assertForbidden();
        $this->actingAs($client)->get(route('client-operations.projects.show', $this->projectB))->assertForbidden();
        $this->actingAs($client)->get(route('client-operations.stations.show', $this->stationB))->assertForbidden();
        $this->actingAs($client)->get(route('client-operations.corridors.show', [$this->projectA, $this->corridorB]))->assertForbidden();
    }

    public function test_client_ui_does_not_grant_asset_registry_mutation(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->post(route('project-monitoring-stations.store'), [
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->stationA->workspace_id,
            'corridor_id' => $this->corridorA->id,
            'station_code' => 'CLIENT-CREATE',
            'name' => 'Client Created Station',
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'status' => 'Normal',
        ])->assertForbidden();
    }

    private function clientUser(Client $client): User
    {
        $user = User::create([
            'name' => 'Client User '.$client->client_code,
            'email' => uniqid('client_', true).'@test.com',
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

    private function projectStack(Client $client, string $suffix): array
    {
        $project = Project::create([
            'project_code' => 'PRJ-'.$suffix,
            'name' => 'Project '.$suffix,
            'client_id' => $client->id,
            'status' => 'Active',
        ]);
        $workspace = GeospatialWorkspace::create([
            'project_id' => $project->id,
            'workspace_code' => 'GWS-'.$suffix,
            'name' => 'Workspace '.$suffix,
            'province' => 'Province '.$suffix,
            'city' => 'City '.$suffix,
            'latitude' => -0.90,
            'longitude' => 100.30,
            'status' => 'Normal',
        ]);
        $corridor = CorridorMonitoring::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_code' => 'COR-'.$suffix,
            'name' => 'Corridor '.$suffix,
            'status' => 'Active',
        ]);
        $station = MonitoringStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_id' => $corridor->id,
            'station_code' => 'MS-'.$suffix,
            'name' => 'Monitoring '.$suffix,
            'station_type' => 'hydromet_monitoring',
            'latitude' => -0.91,
            'longitude' => 100.31,
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'registration_status' => 'registered',
            'service_status' => 'Active',
            'service_period_start' => now()->subMonth()->toDateString(),
            'service_period_end' => now()->addDays(30)->toDateString(),
            'entitlement' => 'standard',
            'package_status' => 'Active',
            'administrative_attention' => $suffix === 'A' ? 'Calibration review' : null,
            'status' => 'Normal',
        ]);
        $logger = DataLogger::create([
            'monitoring_station_id' => $station->id,
            'logger_code' => 'DL-'.$suffix,
            'logger_status' => 'Active',
        ]);
        ConnectivityConfig::create([
            'data_logger_id' => $logger->id,
            'connectivity_code' => 'CONN-'.$suffix,
            'connectivity_status' => 'Online',
            'connection_state' => 'connected',
            'uplink_state' => 'uplink',
            'last_seen_at' => now(),
        ]);
        $prefix = MstPrefix::create(['prefix_code' => 'PFX-'.$suffix, 'name' => 'Prefix '.$suffix, 'status' => 'Active']);
        $sensor = Sensor::create([
            'workspace_id' => $workspace->id,
            'monitoring_station_id' => $station->id,
            'data_logger_id' => $logger->id,
            'mst_prefix_id' => $prefix->id,
            'slave_id' => '1',
            'address' => '0',
            'function_code' => 'FC03',
            'quantity' => 1,
            'poll_interval_ms' => 1000,
            'sensor_code' => 'SNS-'.$suffix,
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

        return [$project, $corridor, $station];
    }
}
