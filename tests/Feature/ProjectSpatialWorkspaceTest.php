<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CorridorMonitoring;
use App\Models\GeospatialWorkspace;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSpatialWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Role $sentinelRole;
    private Role $clientRole;
    private Permission $createPermission;
    private Permission $editPermission;
    private Permission $deletePermission;
    private Client $clientA;
    private Client $clientB;
    private Project $projectA;
    private Project $projectB;
    private GeospatialWorkspace $workspaceA;
    private GeospatialWorkspace $workspaceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPermission = Permission::create([
            'name' => 'project.create',
            'display_name' => 'Create Project',
            'resource' => 'project',
            'action' => 'create',
        ]);
        $this->editPermission = Permission::create([
            'name' => 'project.edit',
            'display_name' => 'Edit Project',
            'resource' => 'project',
            'action' => 'edit',
        ]);
        $this->deletePermission = Permission::create([
            'name' => 'project.delete',
            'display_name' => 'Delete Project',
            'resource' => 'project',
            'action' => 'delete',
        ]);

        $this->sentinelRole = Role::create([
            'name' => 'SentinelSpatial',
            'display_name' => 'Sentinel Spatial',
            'type' => 'system',
        ]);
        $this->clientRole = Role::create([
            'name' => 'ClientAdmin',
            'display_name' => 'Client Admin',
            'type' => 'system',
        ]);

        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);

        $this->projectA = Project::create([
            'project_code' => 'PRJ-A',
            'name' => 'Project A',
            'client_id' => $this->clientA->id,
            'status' => 'Active',
        ]);
        $this->projectB = Project::create([
            'project_code' => 'PRJ-B',
            'name' => 'Project B',
            'client_id' => $this->clientB->id,
            'status' => 'Active',
        ]);

        $this->workspaceA = GeospatialWorkspace::create([
            'project_id' => $this->projectA->id,
            'workspace_code' => 'GWS-A',
            'name' => 'Workspace A',
            'province' => 'A',
            'status' => 'Normal',
        ]);
        $this->workspaceB = GeospatialWorkspace::create([
            'project_id' => $this->projectB->id,
            'workspace_code' => 'GWS-B',
            'name' => 'Workspace B',
            'province' => 'B',
            'status' => 'Normal',
        ]);
    }

    public function test_project_spatial_resources_are_scoped_to_project(): void
    {
        $clientUser = $this->clientUser($this->clientA);
        $corridorA = $this->corridor($this->projectA, $this->workspaceA, 'COR-A');
        $corridorB = $this->corridor($this->projectB, $this->workspaceB, 'COR-B');

        $response = $this->actingAs($clientUser)
            ->getJson(route('projects.spatial-resources', $this->projectA));

        $response->assertOk()
            ->assertJsonPath('project.project_code', 'PRJ-A')
            ->assertJsonFragment(['id' => $corridorA->id])
            ->assertJsonMissing(['id' => $corridorB->id, 'corridor_code' => 'COR-B']);
    }

    public function test_user_without_create_permission_cannot_create_spatial_objects(): void
    {
        $sentinel = $this->sentinelUser([$this->editPermission]);

        $response = $this->actingAs($sentinel)->post(route('project-corridors.store'), [
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->workspaceA->id,
            'corridor_code' => 'COR-NO-CREATE',
            'name' => 'No Create Corridor',
            'path_coordinates' => '[[-0.92,100.36],[-0.91,100.38]]',
            'status' => 'Planned',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('corridor_monitorings', ['corridor_code' => 'COR-NO-CREATE']);
    }

    public function test_edit_and_delete_permissions_are_enforced_separately_for_corridors(): void
    {
        $sentinel = $this->sentinelUser([$this->editPermission]);
        $corridor = $this->corridor($this->projectA, $this->workspaceA, 'COR-EDIT');

        $editResponse = $this->actingAs($sentinel)->post(route('project-corridors.store'), [
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->workspaceA->id,
            'corridor_code' => 'COR-EDIT',
            'name' => 'Edited Corridor',
            'path_coordinates' => '[[-0.92,100.36],[-0.91,100.38]]',
            'status' => 'Active',
        ]);

        $editResponse->assertRedirect();
        $this->assertDatabaseHas('corridor_monitorings', [
            'id' => $corridor->id,
            'name' => 'Edited Corridor',
            'status' => 'Active',
        ]);

        $deleteResponse = $this->actingAs($sentinel)
            ->delete(route('project-setup.destroy', ['type' => 'corridor', 'id' => $corridor->id]));

        $deleteResponse->assertForbidden();
        $this->assertDatabaseHas('corridor_monitorings', ['id' => $corridor->id]);
    }

    public function test_client_cannot_call_asset_registry_mutation_endpoints(): void
    {
        $clientUser = $this->clientUser($this->clientA);

        $response = $this->actingAs($clientUser)->post(route('data-loggers.store'), [
            'logger_code' => 'DL-CLIENT-001',
            'logger_status' => 'Active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('data_loggers', ['logger_code' => 'DL-CLIENT-001']);
    }

    public function test_cross_project_corridor_access_is_blocked(): void
    {
        $clientUser = $this->clientUser($this->clientA);
        $corridorB = $this->corridor($this->projectB, $this->workspaceB, 'COR-BLOCKED');

        $response = $this->actingAs($clientUser)
            ->getJson(route('projects.corridors.show', [$this->projectA, $corridorB]));

        $response->assertForbidden();
    }

    private function sentinelUser(array $permissions): User
    {
        $this->sentinelRole->permissions()->sync(collect($permissions)->pluck('id')->all());

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

    private function corridor(Project $project, GeospatialWorkspace $workspace, string $code): CorridorMonitoring
    {
        return CorridorMonitoring::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_code' => $code,
            'name' => $code,
            'path_coordinates' => [[-0.92, 100.36], [-0.91, 100.38]],
            'status' => 'Planned',
        ]);
    }
}
