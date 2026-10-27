<?php

namespace Tests\Feature;

use App\Models\CorridorMonitoring;
use App\Models\Client;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\User;
use App\Models\WarningStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSetupNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => collect(glob(database_path('migrations/*.php')))
                ->reject(fn ($path) => str_ends_with($path, '_import_local_resq_data_snapshot.php'))
                ->values()->all(),
            '--realpath' => true,
        ];
    }

    public function test_each_sidebar_destination_renders_only_its_own_section(): void
    {
        $this->actingAs($this->user());
        $sections = [
            'project' => 'project', 'geospatial' => 'geospatial',
            'monitoring-stations' => 'monitoring', 'warning-stations' => 'warning',
            'sensors' => 'data', 'canonical' => 'canonical', 'operations' => 'operation',
        ];

        foreach ($sections as $section => $element) {
            $url = $section === 'project' ? route('projects.index') : route('project-setup.section', $section);
            $response = $this->get($url)->assertOk()->assertViewHas('activeSection', $section)
                ->assertSee('id="'.$element.'-section"', false)
                ->assertDontSee('resq.projectSetup.activeTab')
                ->assertDontSee('data-project-setup-tab-link')
                ->assertDontSee('role="tabpanel"', false)
                ->assertSee('href="'.$url.'" class="active"', false);
            foreach ($sections as $other => $otherElement) {
                if ($other !== $section) {
                    $response->assertDontSee('id="'.$otherElement.'-section"', false);
                }
            }
        }

        // Opening Project after Geospatial always renders Project, including the old alias.
        $this->get(route('project-setup.section', 'geospatial'))->assertOk();
        $this->get(route('projects.index'))->assertOk()->assertViewHas('activeSection', 'project');
        $this->get(route('project-configuration'))->assertOk()->assertViewHas('activeSection', 'project');
    }

    public function test_saving_a_workspace_returns_to_the_geospatial_page(): void
    {
        $user = $this->user();
        $permission = Permission::create(['name' => 'project.create', 'resource' => 'project', 'action' => 'create']);
        $role = Role::create(['name' => 'NavigationManager', 'type' => 'system']);
        $role->permissions()->attach($permission);
        $user->assignRole($role);
        $project = Project::create(['project_code' => 'NAV', 'name' => 'Navigation Project']);
        $url = route('project-setup.section', 'geospatial');

        $this->actingAs($user)->from($url)->post(route('project-workspaces.store'), [
            'project_id' => $project->id, 'workspace_code' => 'NAV-GWS', 'name' => 'Navigation Workspace',
            'province' => 'DKI Jakarta', 'status' => 'Normal',
        ])->assertRedirect($url);
        $this->get($url)->assertOk()->assertViewHas('activeSection', 'geospatial')
            ->assertSee('Navigation Workspace')->assertDontSee('id="project-section"', false);
    }

    public function test_hydrology_link_opens_the_monitoring_page_with_its_corridor(): void
    {
        $this->actingAs($this->user());
        $project = Project::create(['project_code' => 'HYDRO', 'name' => 'Hydrology Project']);
        $workspace = GeospatialWorkspace::create([
            'project_id' => $project->id, 'workspace_code' => 'HYDRO-GWS', 'name' => 'Hydrology Workspace', 'province' => 'DKI Jakarta',
        ]);
        $corridor = CorridorMonitoring::create([
            'project_id' => $project->id, 'workspace_id' => $workspace->id,
            'corridor_code' => 'HYDRO-COR', 'name' => 'Hydrology Corridor',
        ]);
        $url = route('project-setup.section', ['section' => 'monitoring-stations', 'corridor_id' => $corridor->id]);
        $this->get(route('project-setup.section', 'geospatial'))->assertOk()->assertSee($url, false);
        $this->get($url)->assertOk()->assertViewHas('monitoringPreset', [
            'corridor_id' => $corridor->id, 'station_type' => 'hydrological_monitoring',
        ])->assertSee('collapse show', false);
        $this->get(route('project-setup.section', ['section' => 'monitoring-stations', 'corridor_id' => 999999]))->assertNotFound();
    }

    public function test_section_routes_require_authentication_and_preserve_registry_permissions(): void
    {
        $this->get(route('project-setup.section', 'geospatial'))->assertRedirect(route('login'));
        $this->actingAs($this->user('client'));
        foreach (['monitoring-stations', 'warning-stations', 'sensors', 'operations'] as $section) {
            $this->get(route('project-setup.section', $section))->assertForbidden();
        }
        $this->get('/project-setup/invalid')->assertNotFound();
    }

    public function test_registration_can_continue_from_a_new_project_through_every_setup_step(): void
    {
        $this->actingAs($this->manager());
        $response = $this->from(route('projects.index'))->post(route('projects.store'), [
            'project_code' => 'FLOW', 'name' => 'Flow Project', 'status' => 'Active',
        ])->assertSessionHasNoErrors();
        $project = Project::where('project_code', 'FLOW')->firstOrFail();
        $url = fn ($section) => route('project-setup.section', ['section' => $section, 'project_id' => $project->id]);
        $response->assertRedirect($url('geospatial'));
        $this->get($url('geospatial'))->assertOk()->assertViewHas('setupProject', fn ($selected) => $selected->is($project))
            ->assertSee('collapse show', false)->assertSee('Simpan &amp; Next', false)
            ->assertSee('href="'.route('projects.index', ['project_id' => $project->id]).'" class="btn btn-outline-secondary" data-setup-previous', false)
            ->assertSee('href="'.$url('monitoring-stations').'" class="btn btn-primary" data-setup-next', false)
            ->assertSee('href="'.route('projects.index').'" class="btn btn-light">Daftar Project', false)
            ->assertDontSee('aria-label="Alur registrasi project"', false)
            ->assertDontSee('aria-current="step"', false);

        $this->from($url('geospatial'))->post(route('project-workspaces.store'), [
            'project_id' => $project->id, 'workspace_code' => 'FLOW-GWS', 'name' => 'Flow Workspace',
            'province' => 'DKI Jakarta', 'status' => 'Normal', 'setup_next' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect($url('monitoring-stations'));
        $workspace = GeospatialWorkspace::where('workspace_code', 'FLOW-GWS')->firstOrFail();
        $this->get($url('monitoring-stations'))->assertOk()->assertSee('<input name="station_code"', false)
            ->assertSee('collapse show', false);
        $this->from($url('monitoring-stations'))->post(route('project-monitoring-stations.store'), [
            'workspace_id' => $workspace->id, 'station_code' => 'FLOW-MON', 'name' => 'Flow Monitoring',
            'station_type' => 'environmental_monitoring', 'logger_status' => 'Active',
            'connectivity_status' => 'Offline', 'status' => 'Normal', 'setup_next' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect($url('warning-stations'));
        $station = MonitoringStation::where('station_code', 'FLOW-MON')->firstOrFail();
        $this->from($url('warning-stations'))->post(route('project-warning-stations.store'), [
            'workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id,
            'station_code' => 'FLOW-WARN', 'name' => 'Flow Warning', 'controller_status' => 'Standby',
            'status' => 'Normal', 'setup_next' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect($url('sensors'));
        $warning = WarningStation::where('station_code', 'FLOW-WARN')->firstOrFail();
        $prefix = MstPrefix::create(['prefix_code' => 'FLOW-PFX', 'name' => 'Flow Prefix', 'status' => 'Active']);
        $this->from($url('sensors'))->post(route('project-sensors.store'), [
            'workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id, 'warning_station_id' => $warning->id,
            'mst_prefix_id' => $prefix->id, 'sensor_code' => 'FLOW-SENSOR', 'type' => 'water_level',
            'slave_id' => '1', 'address' => '0', 'function_code' => 'FC03', 'quantity' => 1,
            'poll_interval_ms' => 1000, 'data_type' => 'float32', 'scale_factor' => 1, 'offset' => 0,
            'reading_method' => 'Polling', 'alert_level' => 'Normal', 'status' => 'Normal', 'setup_next' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect($url('canonical'));
        $this->get($url('canonical'))->assertOk()
            ->assertSee('href="'.$url('operations').'" class="btn btn-primary" data-setup-next', false);
        $this->get($url('operations'))->assertOk()->assertSee('Buka Dashboard')
            ->assertSee(route('dashboard.projects.map', $project), false);
        $this->assertDatabaseHas('sensors', ['sensor_code' => 'FLOW-SENSOR', 'workspace_id' => $workspace->id]);

    }

    public function test_next_links_keep_the_selected_project_and_only_its_resources(): void
    {
        $this->actingAs($this->user());
        $selected = Project::create(['project_code' => 'SELECTED', 'name' => 'Selected Project']);
        $other = Project::create(['project_code' => 'OTHER', 'name' => 'Other Project']);
        foreach ([$selected, $other] as $project) {
            GeospatialWorkspace::create([
                'project_id' => $project->id, 'workspace_code' => $project->project_code.'-GWS',
                'name' => $project->name.' Workspace', 'province' => 'DKI Jakarta',
            ]);
        }
        $this->get(route('project-setup.section', ['section' => 'geospatial', 'project_id' => $selected->id]))
            ->assertOk()->assertSee('Selected Project Workspace')->assertDontSee('Other Project Workspace')
            ->assertViewHas('projects', fn ($projects) => $projects->pluck('db_id')->all() === [$selected->id])
            ->assertSee('href="'.route('project-setup.section', ['section' => 'monitoring-stations', 'project_id' => $selected->id]).'" class="btn btn-primary" data-setup-next', false);
        $this->get(route('projects.index'))->assertOk()->assertSee('Selected Project')->assertSee('Other Project')->assertSee('Lanjut Setup');
    }

    public function test_updating_a_sensor_ignores_its_own_prefix_slave_and_address(): void
    {
        $user = $this->user();
        $user->update(['status' => 'active']);
        $role = Role::create(['name' => 'SensorEditor', 'type' => 'system', 'is_super_admin' => true]);
        $user->assignRole($role);
        $project = Project::create(['project_code' => 'SENSOR-EDIT', 'name' => 'Sensor Edit Project']);
        $workspace = GeospatialWorkspace::create([
            'project_id' => $project->id, 'workspace_code' => 'SENSOR-EDIT-GWS', 'name' => 'Sensor Edit Workspace', 'province' => 'DKI Jakarta',
        ]);
        $station = MonitoringStation::create([
            'project_id' => $project->id, 'workspace_id' => $workspace->id,
            'station_code' => 'SENSOR-EDIT-MON', 'name' => 'Sensor Edit Monitoring',
        ]);
        $prefix = MstPrefix::create(['prefix_code' => 'SENSOR-EDIT-PFX', 'name' => 'Sensor Edit Prefix', 'status' => 'Active']);
        $sensor = Sensor::create([
            'workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id,
            'mst_prefix_id' => $prefix->id, 'sensor_code' => 'SENSOR-EDIT-01', 'type' => 'water_level',
            'slave_id' => '1', 'address' => '0', 'function_code' => 'FC03', 'quantity' => 1,
            'poll_interval_ms' => 1000, 'data_type' => 'float32', 'scale_factor' => 1, 'offset' => 0,
            'reading_method' => 'Polling', 'alert_level' => 'Normal', 'status' => 'Normal',
        ]);

        $this->actingAs($user)->from(route('project-setup.section', 'sensors'))->post(route('project-sensors.store'), [
            'sensor_id' => $sensor->id,
            'workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id,
            'mst_prefix_id' => $prefix->id, 'sensor_code' => $sensor->sensor_code, 'type' => 'water_level',
            'slave_id' => '1', 'address' => '0', 'function_code' => 'FC03', 'quantity' => 1,
            'poll_interval_ms' => 1000, 'data_type' => 'float32', 'scale_factor' => 1, 'offset' => 0,
            'parameter' => 'Updated water level', 'reading_method' => 'Polling', 'alert_level' => 'Normal', 'status' => 'Normal',
        ])->assertSessionHasNoErrors()->assertRedirect(route('project-setup.section', 'sensors'));

        $this->assertDatabaseHas('sensors', ['id' => $sensor->id, 'parameter' => 'Updated water level']);
    }

    public function test_invalid_registration_stays_on_its_step_and_does_not_advance(): void
    {
        $this->actingAs($this->manager());
        $this->from(route('projects.index'))->post(route('projects.store'), ['project_code' => 'INVALID', 'setup_next' => 1])
            ->assertRedirect(route('projects.index'))->assertSessionHasErrors(['name', 'status']);
        $this->assertDatabaseMissing('resq_projects', ['project_code' => 'INVALID']);
        $project = Project::create(['project_code' => 'VALIDATION', 'name' => 'Validation Project']);
        $url = route('project-setup.section', ['section' => 'geospatial', 'project_id' => $project->id]);
        $this->from($url)->post(route('project-workspaces.store'), [
            'project_id' => $project->id, 'workspace_code' => 'INVALID-GWS', 'setup_next' => 1,
        ])->assertRedirect($url)->assertSessionHasErrors(['name', 'province', 'status'])
            ->assertSessionHasInput('workspace_code', 'INVALID-GWS');
        $this->assertDatabaseMissing('geospatial_workspaces', ['workspace_code' => 'INVALID-GWS']);
    }

    public function test_project_context_cannot_open_an_inaccessible_or_missing_project(): void
    {
        $client = Client::create(['client_code' => 'NAV-CLIENT', 'name' => 'Navigation Client']);
        $project = Project::create(['project_code' => 'PRIVATE', 'name' => 'Private Project']);
        $user = $this->user('client');
        $user->update(['client_id' => $client->id]);
        $this->actingAs($user)->get(route('project-setup.section', ['section' => 'geospatial', 'project_id' => $project->id]))->assertForbidden();
        $this->get(route('projects.index', ['project_id' => $project->id]))->assertForbidden();
        $this->get(route('project-setup.section', ['section' => 'geospatial', 'project_id' => 999999]))->assertNotFound();
    }

    private function manager(): User
    {
        $user = $this->user();
        $permission = Permission::create(['name' => 'project.create', 'resource' => 'project', 'action' => 'create']);
        $role = Role::create(['name' => 'SetupManager', 'type' => 'system']);
        $role->permissions()->attach($permission);
        $user->assignRole($role);

        return $user;
    }

    private function user(string $type = 'sentinel'): User
    {
        return User::factory()->create(['type' => $type, 'dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg']);
    }
}
