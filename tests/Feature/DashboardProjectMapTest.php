<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Models\WarningStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardProjectMapTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        // Build the schema without importing the developer's local data snapshot.
        return [
            '--path' => collect(glob(database_path('migrations/*.php')))
                ->reject(fn ($path) => str_ends_with($path, '_import_local_resq_data_snapshot.php'))
                ->values()->all(),
            '--realpath' => true,
        ];
    }

    public function test_dashboard_lists_projects_in_a_grid_without_loading_a_map(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        $project = Project::create(['project_code' => 'PRJ-CARD', 'name' => 'Project Card', 'status' => 'Active']);

        foreach (['dashboard', 'root'] as $route) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('Daftar Project')
                ->assertSee('id="dashboard-project-grid"', false)
                ->assertSee($project->name)
                ->assertSee(route('dashboard.projects.map', $project), false)
                ->assertDontSee('id="sensor-cluster-map"', false)
                ->assertDontSee('leaflet.js', false);
        }
    }

    public function test_empty_dashboard_displays_an_empty_state(): void
    {
        $this->actingAs(User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada project');
    }

    public function test_dashboard_paginates_filtered_results_and_preserves_filters_in_page_links(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        foreach (range(1, 12) as $number) {
            Project::create([
                'project_code' => 'GRID-'.str_pad($number, 2, '0', STR_PAD_LEFT),
                'name' => 'Grid Project '.str_pad($number, 2, '0', STR_PAD_LEFT),
                'status' => 'Active',
            ]);
        }
        Project::create(['project_code' => 'OTHER', 'name' => 'Other Project', 'status' => 'Inactive']);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'q' => 'grid', 'per_page' => 10, 'page' => 2,
        ]))->assertOk()->assertSee('Grid Project 11')->assertSee('Grid Project 12')
            ->assertDontSee('Grid Project 01')->assertDontSee('Other Project');

        $projects = $response->viewData('projects');
        $this->assertSame(12, $projects->total());
        $this->assertSame(2, $projects->count());
        $this->assertSame(11, $projects->firstItem());
        parse_str(parse_url($projects->previousPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame(['q' => 'grid', 'per_page' => '10', 'page' => '1'], $query);
    }

    public function test_dashboard_combines_search_and_region_filters(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        $client = Client::create(['client_code' => 'FILTER-CLIENT', 'name' => 'Filter Client']);
        $target = $this->projectWithLocations('FILTER');
        $target->update(['client_id' => $client->id, 'status' => 'Active']);
        $target->workspaces()->update(['city' => 'Jakarta Utara']);
        $other = $this->projectWithLocations('FILTER-OTHER');
        $other->update(['client_id' => $client->id, 'status' => 'Inactive']);
        $other->workspaces()->update(['city' => 'Jakarta Selatan']);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'q' => 'prj-filter', 'province' => 'DKI Jakarta', 'city' => 'Jakarta Utara',
            'per_page' => 25,
        ]))->assertOk();
        $this->assertSame([$target->id], $response->viewData('projects')->getCollection()->modelKeys());
        $this->assertSame(25, $response->viewData('projects')->perPage());
        $this->assertSame(['Jakarta Selatan', 'Jakarta Utara'], $response->viewData('cities')->all());

        $this->get(route('dashboard', ['q' => 'missing project']))->assertOk()
            ->assertSee('Tidak ada project yang sesuai filter');
    }

    public function test_province_and_city_must_belong_to_the_same_workspace(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        $project = $this->projectWithLocations('REGION');
        $project->workspaces()->update(['city' => 'Jakarta Utara']);
        $project->workspaces()->create(['workspace_code' => 'OTHER-REGION', 'name' => 'Bogor Workspace', 'province' => 'Jawa Barat', 'city' => 'Bogor']);

        $response = $this->actingAs($user)->get(route('dashboard', ['province' => 'DKI Jakarta', 'city' => 'Bogor']))
            ->assertOk()->assertSee('Tidak ada project yang sesuai filter');
        $this->assertSame(0, $response->viewData('projects')->total());
        $this->assertSame(['Jakarta Utara'], $response->viewData('cities')->all());
    }

    public function test_project_opens_a_standalone_map_with_only_its_own_data(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        $project = $this->projectWithLocations('A');
        $this->projectWithLocations('B');

        $this->actingAs($user)->get(route('dashboard.projects.map', $project))
            ->assertOk()
            ->assertSee('id="sensor-cluster-map"', false)
            ->assertSee('Workspace A')
            ->assertSee('SENSOR-A')
            ->assertSee('WARN-A')
            ->assertDontSee('Workspace B')
            ->assertDontSee('SENSOR-B')
            ->assertDontSee('WARN-B')
            ->assertDontSee('id="sidebar-menu"', false)
            ->assertDontSee('id="page-topbar"', false)
            ->assertSee(json_encode(route('dashboard.projects.map-data', $project, false)), false)
            ->assertSee('project_id='.$project->id, false)
            ->assertSee('var mapMinZoom = 12;', false)
            ->assertSee('var mapMaxZoom = 16;', false);

        $this->getJson(route('dashboard.projects.map-data', $project))
            ->assertOk()
            ->assertJsonCount(1, 'clusters')
            ->assertJsonCount(1, 'sensors')
            ->assertJsonCount(1, 'warningStations')
            ->assertJsonPath('clusters.0.name', 'Workspace A')
            ->assertJsonPath('sensors.0.name', 'SENSOR-A')
            ->assertJsonPath('warningStations.0.name', 'WARN-A - Warning A');
    }

    public function test_clients_only_see_assigned_projects_and_cannot_open_other_maps(): void
    {
        $client = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $otherClient = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'client', 'client_id' => $client->id]);
        $assigned = Project::create(['project_code' => 'ASSIGNED', 'name' => 'Assigned Project', 'client_id' => $client->id]);
        $unassigned = Project::create(['project_code' => 'UNASSIGNED', 'name' => 'Unassigned Project', 'client_id' => $client->id]);
        $foreign = Project::create(['project_code' => 'FOREIGN', 'name' => 'Foreign Project', 'client_id' => $otherClient->id]);
        $user->projects()->attach($assigned->id, ['access_level' => 'viewer']);
        $assigned->workspaces()->create(['workspace_code' => 'VISIBLE', 'name' => 'Visible', 'province' => 'Visible Province']);
        $unassigned->workspaces()->create(['workspace_code' => 'PRIVATE', 'name' => 'Private', 'province' => 'Private Province']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Assigned Project')
            ->assertDontSee('Unassigned Project')
            ->assertDontSee('Foreign Project')
            ->assertSee('Visible Province')->assertDontSee('Private Province')
            ->assertViewHas('totalProjects', 1);

        $this->get(route('dashboard.projects.map', $assigned))->assertOk();
        foreach ([$unassigned, $foreign] as $project) {
            $this->get(route('dashboard.projects.map', $project))->assertForbidden();
            $this->getJson(route('dashboard.projects.map-data', $project))->assertForbidden();
            $this->getJson(route('dashboard.projects.tde-data', $project))->assertForbidden();
        }
    }

    public function test_map_routes_require_authentication_and_reject_missing_projects(): void
    {
        $project = Project::create(['project_code' => 'AUTH', 'name' => 'Authenticated Project']);
        $this->get(route('dashboard.projects.map', $project))->assertRedirect(route('login'));
        $this->getJson(route('dashboard.projects.map-data', $project))->assertUnauthorized();
        $this->getJson(route('dashboard.projects.tde-data', $project))->assertUnauthorized();

        $this->actingAs(User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']))
            ->get(route('dashboard.projects.map', 999999))->assertNotFound();
    }

    public function test_overlay_receives_latest_sensor_values_units_and_warning_status(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        $project = $this->projectWithLocations('LIVE');
        $sensor = Sensor::where('sensor_code', 'SENSOR-LIVE')->firstOrFail();
        $sensor->update(['unit' => 'm']);
        TelemetryReading::create([
            'sensor_id' => $sensor->id, 'value' => '2.5',
            'status' => 'Normal', 'alert_level' => 'Waspada', 'received_at' => now(),
        ]);

        $this->actingAs($user)->get(route('dashboard.projects.map', $project))
            ->assertOk()->assertSee('Sensor &amp; Status', false)->assertSee('Hasil TDE')
            ->assertSee('Aktifkan sirene')->assertSee('data-monitor="siren-toggle"', false);
        $this->getJson(route('dashboard.projects.map-data', $project))
            ->assertOk()->assertJsonPath('sensors.0.value', '2.5 m')
            ->assertJsonPath('sensors.0.alert_level', 'Waspada');

        TelemetryReading::create([
            'sensor_id' => $sensor->id, 'value' => '12.5',
            'status' => 'Normal', 'alert_level' => 'Normal', 'received_at' => now(),
        ]);
        $this->getJson(route('dashboard.projects.map-data', $project))
            ->assertOk()->assertJsonPath('sensors.0.value', '12.5 m')
            ->assertJsonPath('sensors.0.status', 'Awas');
    }

    public function test_sensors_without_coordinates_remain_available_to_the_overlay(): void
    {
        $project = $this->projectWithLocations('NO-GPS');
        $project->workspaces()->update(['province' => 'Unmapped', 'latitude' => null, 'longitude' => null]);
        $project->monitoringStations()->update(['latitude' => null, 'longitude' => null]);
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);

        $this->actingAs($user)->getJson(route('dashboard.projects.map-data', $project))
            ->assertOk()->assertJsonCount(1, 'sensors')->assertJsonPath('sensors.0.lat', null)
            ->assertJsonPath('sensors.0.name', 'SENSOR-NO-GPS');
    }

    public function test_tde_results_are_calculated_for_the_selected_project_and_report_insufficient_data(): void
    {
        $project = $this->projectWithLocations('TDE');
        $this->projectWithLocations('OTHER');
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);

        $this->actingAs($user)->getJson(route('dashboard.projects.tde-data', $project))
            ->assertOk()->assertJsonCount(1, 'stations')
            ->assertJsonPath('stations.0.station_code', 'MON-TDE')
            ->assertJsonPath('stations.0.tde.execution_state', 'insufficient_data');

        $sensor = Sensor::where('sensor_code', 'SENSOR-TDE')->firstOrFail();
        foreach ([[30, 30, 60, 1006.8], [1, 28, 80, 1007.2]] as [$minutes, $temperature, $humidity, $pressure]) {
            TelemetryReading::create([
                'sensor_id' => $sensor->id, 'value' => 'Weather Station',
                'parameter_values' => [
                    ['parameter' => 'Temperature', 'value' => $temperature, 'unit' => 'degC'],
                    ['parameter' => 'Humidity', 'value' => $humidity, 'unit' => '%RH'],
                    ['parameter' => 'Pressure', 'value' => $pressure, 'unit' => 'hPa'],
                    ['parameter' => 'Rainfall', 'value' => 0, 'unit' => 'mm'],
                ],
                'received_at' => now()->subMinutes($minutes),
            ]);
        }
        $this->getJson(route('dashboard.projects.tde-data', $project))
            ->assertOk()->assertJsonPath('stations.0.tde.execution_state', 'evaluated')
            ->assertJsonPath('stations.0.tde.output.combination_pattern.key', '<>><=')
            ->assertJsonPath('stations.0.tde.output.diagnosis.fallback_interpretation.summary', 'Arah Kering -> Basah');
    }

    private function projectWithLocations(string $suffix): Project
    {
        $project = Project::create(['project_code' => 'PRJ-'.$suffix, 'name' => 'Project '.$suffix]);
        $workspace = GeospatialWorkspace::create([
            'project_id' => $project->id,
            'workspace_code' => 'GWS-'.$suffix,
            'name' => 'Workspace '.$suffix,
            'province' => 'DKI Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);
        $station = MonitoringStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'station_code' => 'MON-'.$suffix,
            'name' => 'Monitoring '.$suffix,
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);
        $warning = WarningStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'station_code' => 'WARN-'.$suffix,
            'name' => 'Warning '.$suffix,
        ]);
        Sensor::create([
            'workspace_id' => $workspace->id,
            'monitoring_station_id' => $station->id,
            'warning_station_id' => $warning->id,
            'sensor_code' => 'SENSOR-'.$suffix,
            'type' => 'Water Level',
            'value' => '1',
            'threshold' => '10',
        ]);

        return $project;
    }
}
