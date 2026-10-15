<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
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

    public function test_dashboard_lists_project_cards_without_loading_a_map(): void
    {
        $user = User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']);
        $project = Project::create(['project_code' => 'PRJ-CARD', 'name' => 'Project Card', 'status' => 'Active']);

        foreach (['dashboard', 'root'] as $route) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('Daftar Project')
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

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Assigned Project')
            ->assertDontSee('Unassigned Project')
            ->assertDontSee('Foreign Project');

        $this->get(route('dashboard.projects.map', $assigned))->assertOk();
        foreach ([$unassigned, $foreign] as $project) {
            $this->get(route('dashboard.projects.map', $project))->assertForbidden();
            $this->getJson(route('dashboard.projects.map-data', $project))->assertForbidden();
        }
    }

    public function test_map_routes_require_authentication_and_reject_missing_projects(): void
    {
        $project = Project::create(['project_code' => 'AUTH', 'name' => 'Authenticated Project']);
        $this->get(route('dashboard.projects.map', $project))->assertRedirect(route('login'));
        $this->getJson(route('dashboard.projects.map-data', $project))->assertUnauthorized();

        $this->actingAs(User::factory()->state(['dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg'])->create(['type' => 'sentinel']))
            ->get(route('dashboard.projects.map', 999999))->assertNotFound();
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
