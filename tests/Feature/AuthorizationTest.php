<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sentinelUser;
    protected User $clientUserA;
    protected User $clientUserB;
    protected Client $clientA;
    protected Client $clientB;
    protected Project $projectA;
    protected Project $projectB;
    protected Role $sentinelAdminRole;
    protected Role $clientAdminRole;
    protected Permission $projectEditPerm;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->sentinelAdminRole = Role::create([
            'name' => 'SentinelAdmin',
            'display_name' => 'Sentinel Administrator',
            'type' => 'system',
        ]);

        $this->clientAdminRole = Role::create([
            'name' => 'ClientAdmin',
            'display_name' => 'Client Administrator',
            'type' => 'system',
        ]);

        $this->projectEditPerm = Permission::create([
            'name' => 'project.edit',
            'display_name' => 'Edit Project',
            'resource' => 'project',
            'action' => 'edit',
        ]);

        $this->sentinelAdminRole->permissions()->attach($this->projectEditPerm);

        // Create sentinel user
        $this->sentinelUser = User::create([
            'name' => 'Sentinel Admin',
            'email' => 'sentinel@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'sentinel',
            'status' => 'active',
        ]);
        $this->sentinelUser->assignRole($this->sentinelAdminRole);

        // Create clients
        $this->clientA = Client::create([
            'client_code' => 'CLIENT_A',
            'name' => 'Client A',
            'status' => 'active',
        ]);

        $this->clientB = Client::create([
            'client_code' => 'CLIENT_B',
            'name' => 'Client B',
            'status' => 'active',
        ]);

        // Create client users
        $this->clientUserA = User::create([
            'name' => 'Client A User',
            'email' => 'user_a@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $this->clientA->id,
            'status' => 'active',
        ]);
        $this->clientUserA->assignRole($this->clientAdminRole);

        $this->clientUserB = User::create([
            'name' => 'Client B User',
            'email' => 'user_b@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $this->clientB->id,
            'status' => 'active',
        ]);
        $this->clientUserB->assignRole($this->clientAdminRole);

        // Create projects
        $this->projectA = Project::create([
            'project_code' => 'PROJ_A',
            'name' => 'Project A',
            'client_id' => $this->clientA->id,
            'status' => 'active',
        ]);

        $this->projectB = Project::create([
            'project_code' => 'PROJ_B',
            'name' => 'Project B',
            'client_id' => $this->clientB->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test that Client A user cannot access Client B's project.
     */
    public function test_client_user_cannot_access_other_client_project(): void
    {
        $this->actingAs($this->clientUserA);

        $can = $this->clientUserA->canAccessProject($this->projectB);
        $this->assertFalse($can, 'Client A user should not access Client B project.');
    }

    /**
     * Test that Client A user can access their own project.
     */
    public function test_client_user_can_access_own_project(): void
    {
        $this->actingAs($this->clientUserA);

        $can = $this->clientUserA->canAccessProject($this->projectA);
        $this->assertTrue($can, 'Client A user should access their own project.');
    }

    /**
     * Test that permission independence: edit does not imply delete.
     */
    public function test_project_edit_permission_does_not_imply_delete(): void
    {
        $this->sentinelUser->assignRole($this->sentinelAdminRole);

        $hasEditPerm = $this->sentinelUser->hasPermissionTo('project.edit');
        $hasDeletePerm = $this->sentinelUser->hasPermissionTo('project.delete');

        $this->assertTrue($hasEditPerm, 'Should have edit permission.');
        $this->assertFalse($hasDeletePerm, 'Should NOT have delete permission.');
    }

    /**
     * Test that create permission exists independently.
     */
    public function test_create_permission_is_independent(): void
    {
        $createPerm = Permission::create([
            'name' => 'project.create',
            'display_name' => 'Create Project',
            'resource' => 'project',
            'action' => 'create',
        ]);

        $this->sentinelAdminRole->permissions()->syncWithoutDetaching([$createPerm->id]);

        $hasCreatePerm = $this->sentinelUser->hasPermissionTo('project.create');
        $this->assertTrue($hasCreatePerm, 'Should have create permission.');
    }

    /**
     * Test recovery account foundation exists.
     */
    public function test_project_can_have_recovery_account(): void
    {
        $recovery = $this->projectA->recoveryAccount()->create([
            'recovery_username' => 'recovery_' . $this->projectA->id,
            'recovery_password_hash' => bcrypt('recovery_password'),
            'status' => 'unused',
        ]);

        $this->assertNotNull($recovery);
        $this->assertTrue($recovery->isUnused());
        $this->assertEquals($recovery->project_id, $this->projectA->id);
    }

    /**
     * Test that sentinel user is sentinel type.
     */
    public function test_sentinel_user_type(): void
    {
        $this->assertTrue($this->sentinelUser->isSentinel());
        $this->assertFalse($this->sentinelUser->isClientUser());
    }

    /**
     * Test that client user is client type.
     */
    public function test_client_user_type(): void
    {
        $this->assertTrue($this->clientUserA->isClientUser());
        $this->assertFalse($this->clientUserA->isSentinel());
    }

    /**
     * Test that user status is tracked.
     */
    public function test_user_status_tracking(): void
    {
        $this->assertTrue($this->sentinelUser->isActive());

        $this->sentinelUser->update(['status' => 'suspended']);
        $this->assertFalse($this->sentinelUser->isActive());
    }
}
