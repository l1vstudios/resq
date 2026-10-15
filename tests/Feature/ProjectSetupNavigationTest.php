<?php

namespace Tests\Feature;

use App\Models\CorridorMonitoring;
use App\Models\GeospatialWorkspace;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
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

    private function user(string $type = 'sentinel'): User
    {
        return User::factory()->create(['type' => $type, 'dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg']);
    }
}
