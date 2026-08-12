<?php

namespace Tests\Feature;

use App\Models\CanonicalParameter;
use App\Models\Client;
use App\Models\CorridorMonitoring;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\ReferencePoint;
use App\Models\ReferenceRoute;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\StationFunctionConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFunctionConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private Role $clientRole;
    private Client $clientA;
    private Client $clientB;
    private ProjectStack $stackA;
    private ProjectStack $stackB;
    private MonitoringStation $limitedStation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);
        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $this->stackA = $this->projectStack($this->clientA, 'A');
        $this->stackB = $this->projectStack($this->clientB, 'B');

        $this->mapSensor($this->sensor($this->stackA->station, 'RAIN-A', 'rainfall'), 'Rainfall', 'meteorology', 'mm');
        $this->mapSensor($this->sensor($this->stackA->station, 'LEVEL-A', 'water_level'), 'WaterLevel', 'hydrology', 'm');
        $this->mapSensor($this->sensor($this->stackA->station, 'VEL-A', 'water_velocity'), 'WaterVelocity', 'hydrology', 'm/s');
        $this->mapSensor($this->sensor($this->stackB->station, 'RAIN-B', 'rainfall'), 'Rainfall', 'meteorology', 'mm');

        $this->limitedStation = MonitoringStation::create([
            'project_id' => $this->stackA->project->id,
            'workspace_id' => $this->stackA->workspace->id,
            'station_code' => 'MS-LIMITED',
            'name' => 'Limited Station',
            'station_type' => 'hydromet_monitoring',
            'latitude' => -0.94,
            'longitude' => 100.34,
            'status' => 'Normal',
        ]);
        $this->mapSensor($this->sensor($this->limitedStation, 'TEMP-A', 'temperature'), 'Temperature', 'meteorology', 'C');
    }

    public function test_main_menu_station_selector_shows_only_supported_functions(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->get(route('client-operations.function-configuration.index'))
            ->assertOk()
            ->assertSee('Select Monitoring Station')
            ->assertSee('MS-A')
            ->assertSee('TDE')
            ->assertSee('Discharge')
            ->assertSee('CFPE')
            ->assertSee('MS-LIMITED')
            ->assertDontSee('MS-B');
    }

    public function test_station_ui_links_to_same_function_configuration_page(): void
    {
        $client = $this->clientUser($this->clientA);
        $configurationUrl = route('client-operations.function-configuration.stations.show', $this->stackA->station);

        $this->actingAs($client)->get(route('client-operations.stations.show', $this->stackA->station))
            ->assertOk()
            ->assertSee($configurationUrl, false)
            ->assertSee('Function Configuration');

        $this->actingAs($client)->get($configurationUrl)
            ->assertOk()
            ->assertSee('MS-A Function Configuration')
            ->assertSee(route('client-operations.function-configuration.store', [$this->stackA->station, 'TDE']), false)
            ->assertSee(route('client-operations.function-configuration.store', [$this->stackA->station, 'Discharge']), false)
            ->assertSee(route('client-operations.function-configuration.store', [$this->stackA->station, 'CFPE']), false);
    }

    public function test_client_can_configure_tde_data_window_without_system_internals(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->post(route('client-operations.function-configuration.store', [$this->stackA->station, 'TDE']), [
            'reading_method' => 'Moving Average',
            'data_window_value' => 6,
            'data_window_unit' => 'hours',
            'status' => 'draft',
        ])->assertRedirect(route('client-operations.function-configuration.stations.show', $this->stackA->station));

        $configuration = StationFunctionConfiguration::where('monitoring_station_id', $this->stackA->station->id)
            ->where('function_name', 'TDE')
            ->firstOrFail();

        $this->assertSame('Moving Average', $configuration->reading_method);
        $this->assertSame(['value' => 6, 'unit' => 'hours'], $configuration->configuration['data_window']);
        $this->assertContains('analytical_matrix', $configuration->configuration['system_internals_locked']);
        $this->assertNotEmpty($configuration->unresolved_analytical_rules);
    }

    public function test_client_can_configure_discharge_parameters_without_calculation_formula(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->post(route('client-operations.function-configuration.store', [$this->stackA->station, 'Discharge']), [
            'reading_method' => 'Absolute',
            'cross_sectional_area' => '12.5',
            'manning_n' => '0.031',
            'coefficient_cd' => '0.72',
            'unit' => 'm3/s',
            'status' => 'draft',
        ])->assertRedirect();

        $configuration = StationFunctionConfiguration::where('monitoring_station_id', $this->stackA->station->id)
            ->where('function_name', 'Discharge')
            ->firstOrFail();

        $this->assertSame(12.5, $configuration->configuration['cross_sectional_area']);
        $this->assertSame(0.031, $configuration->configuration['manning_n']);
        $this->assertSame(0.72, $configuration->configuration['coefficient_cd']);
        $this->assertSame('backend_only', $configuration->configuration['calculation_runtime']);
        $this->assertArrayNotHasKey('formula', $configuration->configuration);
    }

    public function test_client_can_bind_cfpe_to_registered_route_and_bm(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->post(route('client-operations.function-configuration.store', [$this->stackA->station, 'CFPE']), [
            'reading_method' => 'Probability',
            'reference_route_id' => $this->stackA->route->id,
            'reference_bm_id' => $this->stackA->bm->id,
            'offset_direction' => 'centerline',
            'offset_distance' => '3.5',
            'station_ground_zero_chainage' => '125.75',
            'uncertainty_factor' => '0.2',
            'validate' => '1',
            'activate' => '1',
        ])->assertRedirect();

        $configuration = StationFunctionConfiguration::where('monitoring_station_id', $this->stackA->station->id)
            ->where('function_name', 'CFPE')
            ->firstOrFail();

        $this->assertSame('active', $configuration->status);
        $this->assertSame('validated', $configuration->validation_state);
        $this->assertSame($this->stackA->route->id, $configuration->configuration['reference_route_id']);
        $this->assertSame($this->stackA->bm->id, $configuration->configuration['reference_bm_id']);
    }

    public function test_unsupported_function_and_cross_project_bindings_are_rejected(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->post(route('client-operations.function-configuration.store', [$this->limitedStation, 'Discharge']), [
            'reading_method' => 'Absolute',
            'unit' => 'm3/s',
        ])->assertStatus(422);

        $this->actingAs($client)->post(route('client-operations.function-configuration.store', [$this->stackA->station, 'CFPE']), [
            'reading_method' => 'Absolute',
            'reference_route_id' => $this->stackB->route->id,
            'reference_bm_id' => $this->stackB->bm->id,
            'offset_direction' => 'centerline',
        ])->assertForbidden();

        $this->actingAs($client)->get(route('client-operations.function-configuration.stations.show', $this->stackB->station))
            ->assertForbidden();
    }

    public function test_client_cannot_modify_asset_registry_from_function_configuration(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->post(route('project-monitoring-stations.store'), [
            'project_id' => $this->stackA->project->id,
            'workspace_id' => $this->stackA->workspace->id,
            'station_code' => 'CLIENT-FN-CREATE',
            'name' => 'Client Function Create',
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'status' => 'Normal',
        ])->assertForbidden();
    }

    private function clientUser(Client $client): User
    {
        $user = User::create([
            'name' => 'Client User '.$client->client_code,
            'email' => uniqid('client_fn_', true).'@test.com',
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

    private function projectStack(Client $client, string $suffix): ProjectStack
    {
        $project = \App\Models\Project::create([
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
        $route = ReferenceRoute::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'route_code' => 'RR-'.$suffix,
            'name' => 'Reference Route '.$suffix,
            'route_type' => 'river',
            'status' => 'Active',
        ]);
        $corridor = CorridorMonitoring::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'reference_route_id' => $route->id,
            'corridor_code' => 'COR-'.$suffix,
            'name' => 'Corridor '.$suffix,
            'status' => 'Active',
        ]);
        $bm = ReferencePoint::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_id' => $corridor->id,
            'reference_route_id' => $route->id,
            'point_code' => 'BM-'.$suffix,
            'name' => 'Benchmark '.$suffix,
            'point_type' => 'BM',
            'latitude' => -0.91,
            'longitude' => 100.31,
            'status' => 'Active',
        ]);
        $station = MonitoringStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_id' => $corridor->id,
            'station_code' => 'MS-'.$suffix,
            'name' => 'Monitoring '.$suffix,
            'station_type' => 'hydromet_monitoring',
            'latitude' => -0.92,
            'longitude' => 100.32,
            'status' => 'Normal',
        ]);

        return new ProjectStack($project, $workspace, $corridor, $route, $bm, $station);
    }

    private function sensor(MonitoringStation $station, string $code, string $type): Sensor
    {
        return Sensor::create([
            'workspace_id' => $station->workspace_id,
            'monitoring_station_id' => $station->id,
            'sensor_code' => $code,
            'type' => $type,
            'parameter' => $type,
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => '-',
            'reading_method' => 'Absolute',
            'status' => 'Active',
            'last_seen_at' => now(),
        ]);
    }

    private function mapSensor(Sensor $sensor, string $field, string $domain, string $unit): void
    {
        $canonical = CanonicalParameter::firstOrCreate(
            ['field_identity' => $field],
            [
                'definition' => $field,
                'domain' => $domain,
                'canonical_unit' => $unit,
                'data_type' => 'numeric',
                'measurement_characteristic' => 'measured',
                'status' => 'active',
            ]
        );

        SensorMappingProfile::create([
            'sensor_id' => $sensor->id,
            'profile_code' => 'MAP-'.$sensor->sensor_code,
            'source_parameter' => $sensor->parameter,
            'source_unit' => $sensor->unit,
            'canonical_parameter_id' => $canonical->id,
            'value_origin' => 'direct_measurement',
            'status' => 'active',
        ]);
    }
}

class ProjectStack
{
    public function __construct(
        public \App\Models\Project $project,
        public GeospatialWorkspace $workspace,
        public CorridorMonitoring $corridor,
        public ReferenceRoute $route,
        public ReferencePoint $bm,
        public MonitoringStation $station,
    ) {
    }
}
