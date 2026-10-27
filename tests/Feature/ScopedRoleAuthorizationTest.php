<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\NavigationMenu;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\ProjectRoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_role_only_exposes_its_selected_project_and_menu(): void
    {
        $client = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $otherClient = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $selected = Project::create(['project_code' => 'PROJECT-A', 'name' => 'Project A', 'client_id' => $client->id]);
        $other = Project::create(['project_code' => 'PROJECT-B', 'name' => 'Project B', 'client_id' => $otherClient->id]);

        $permission = Permission::create([
            'name' => 'project.edit', 'resource' => 'project', 'action' => 'edit',
        ]);
        $role = Role::create([
            'name' => 'client-a-editor', 'client_id' => $client->id, 'type' => 'custom',
        ]);
        $role->permissions()->attach($permission);

        $menu = NavigationMenu::create(['code' => 'project-map', 'label' => 'Project Map', 'is_project_scoped' => true]);
        $role->menus()->attach($menu, ['can_view' => true]);

        $user = User::factory()->create([
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        app(ProjectRoleAssignmentService::class)->assign($user, $selected, $role);

        $authorization = app(AuthorizationService::class);
        $this->assertTrue($authorization->canAccessProject($user, $selected));
        $this->assertFalse($authorization->canAccessProject($user, $other));
        $this->assertTrue($user->hasPermissionForProject('project.edit', $selected));
        $this->assertFalse($user->hasPermissionForProject('project.edit', $other));
        $this->assertTrue($user->canViewMenu($menu, $selected));
        $this->assertFalse($user->canViewMenu($menu, $other));
        $this->assertSame([$selected->id], $authorization->scopeProjectsForUser($user, Project::query())->pluck('id')->all());
    }

    public function test_super_admin_flag_is_not_coupled_to_a_role_name(): void
    {
        $role = Role::create(['name' => 'operations-root', 'is_super_admin' => true]);
        $user = User::factory()->create([
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'sentinel',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->isSuperAdmin());
    }
}
