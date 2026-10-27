<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\NavigationMenu;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementTest extends TestCase
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

    public function test_admin_management_shows_database_users_and_links_to_the_full_access_form(): void
    {
        $this->admin();
        $user = $this->user(['name' => 'Database User', 'email' => 'real-user@example.test']);
        $response = $this->get(route('admins.index'))->assertOk()
            ->assertSee($user->name)->assertSee($user->email)
            ->assertSee('Tambah Admin')->assertSee('id="admin-user-grid"', false)
            ->assertSee('href="'.route('admins.create').'"', false)
            ->assertDontSee('ops@resq.id')->assertDontSee('User Setup')
            ->assertDontSee('href="contacts-profile"', false)
            ->assertDontSee('change-password')->assertSee('Logout');

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="admin-user-grid"'), strpos($html, 'href="'.route('admins.create').'"'));
        $this->assertStringNotContainsString('add-admin-modal', $html);
        $this->get(route('admins.index', ['q' => 'real-user']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $user->id);
    }

    public function test_full_access_form_has_scope_and_crud_matrix_tabs(): void
    {
        $this->admin();

        $this->get(route('admins.create'))->assertOk()
            ->assertSee('Client &amp; Project', false)
            ->assertSee('Matriks Menu &amp; CRUD', false)
            ->assertSee('Akses penuh')
            ->assertSee('name="permissions[project][]"', false)
            ->assertSee('name="menu_ids[]"', false)
            ->assertSee('data-password-toggle="admin-password"', false)
            ->assertSee('data-password-toggle="admin-password-confirm"', false);
    }

    public function test_client_sidebar_shows_monitoring_when_the_role_can_view_it(): void
    {
        $role = Role::create(['name' => 'client-monitoring-viewer', 'type' => 'custom']);
        $role->menus()->attach(
            NavigationMenu::where('code', 'monitoring')->value('id'),
            ['can_view' => true]
        );
        $clientUser = $this->user(['type' => 'client']);
        $clientUser->assignRole($role);

        $this->actingAs($clientUser)->get(route('dashboard'))->assertOk()
            ->assertSee('href="'.route('monitoring.index').'"', false)
            ->assertSee('>Monitoring<', false);
    }

    public function test_admin_can_create_an_internal_account_with_a_hashed_password_and_role(): void
    {
        $this->admin();
        $role = Role::create(['name' => 'PlatformOperator', 'type' => 'system']);

        $this->post(route('admins.store'), $this->payload($role))
            ->assertRedirect(route('admins.index'))->assertSessionHas('message');
        $user = User::where('email', 'new-admin@example.test')->firstOrFail();
        $this->assertSame('sentinel', $user->type);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('ExamplePassword123', $user->password));
        $this->assertTrue($user->hasRole('PlatformOperator'));
    }

    public function test_admin_can_create_a_project_scoped_full_crud_role_from_the_full_page(): void
    {
        $this->admin();
        $client = Client::create(['client_code' => 'RBAC-CLIENT', 'name' => 'RBAC Client']);
        $project = Project::create(['project_code' => 'RBAC-PROJECT', 'name' => 'RBAC Project', 'client_id' => $client->id]);

        $this->post(route('admins.store'), [
            'name' => 'Scoped Operator',
            'email' => 'scoped-operator@example.test',
            'password' => 'ExamplePassword123',
            'password_confirmation' => 'ExamplePassword123',
            'account_type' => 'client',
            // Form submissions send IDs as strings; client/project matching must
            // therefore not depend on PHP's strict string-vs-integer comparison.
            'client_id' => (string) $client->id,
            'project_ids' => [(string) $project->id],
            'role_mode' => 'new',
            'role_name' => 'rbac-client-operator',
            'role_display_name' => 'RBAC Client Operator',
            'full_access' => '1',
            'status' => 'active',
        ])->assertRedirect(route('admins.index'));

        $user = User::where('email', 'scoped-operator@example.test')->firstOrFail();
        $role = Role::where('name', 'rbac-client-operator')->firstOrFail();
        $this->assertTrue($user->projects()->whereKey($project->id)->exists());
        $this->assertTrue($user->projectRoles()->whereKey($role->id)->wherePivot('project_id', $project->id)->exists());
        $this->assertTrue($user->canAccessProject($project));
        $this->assertTrue($user->hasPermissionForProject('project.edit', $project));
        $this->assertGreaterThan(0, $role->menus()->count());
        $this->assertContains('dashboard', $user->visibleMenuCodes());
    }

    public function test_admin_can_edit_an_account_and_its_role_matrix(): void
    {
        $this->admin();
        $role = Role::create(['name' => 'editable-role', 'type' => 'custom']);
        $user = $this->user(['name' => 'Before Edit', 'email' => 'before-edit@example.test']);
        $user->assignRole($role);
        $menu = NavigationMenu::where('code', 'project')->firstOrFail();
        $permission = Permission::firstOrCreate([
            'name' => Permission::buildName('project', 'edit'),
        ], [
            'display_name' => 'Edit project',
            'resource' => 'project',
            'action' => 'edit',
        ]);
        $role->menus()->attach($menu->id, ['can_view' => true]);
        $role->permissions()->attach($permission->id);

        $response = $this->get(route('admins.edit', $user));
        $response->assertOk()
            ->assertSee('Edit Admin &amp; Hak Akses', false)
            ->assertSee('Before Edit')
            ->assertSee('value="'.$menu->id.'" aria-label="Lihat '.$menu->label.'" checked', false)
            ->assertSee('name="permissions[project][]" value="edit" aria-label="Edit '.$menu->label.'"  checked', false);

        $this->put(route('admins.update', $user), [
            'name' => 'After Edit',
            'email' => 'after-edit@example.test',
            'account_type' => 'sentinel',
            'status' => 'inactive',
            'full_access' => '0',
            'menu_ids' => [$menu->id],
            'permissions' => ['project' => ['view', 'edit']],
        ])->assertRedirect(route('admins.index'));

        $user->refresh();
        $this->assertSame('After Edit', $user->name);
        $this->assertSame('inactive', $user->status);
        $this->assertSame('after-edit@example.test', $user->email);
        $this->assertTrue($role->fresh()->menus()->whereKey($menu->id)->exists());
        $this->assertTrue($role->fresh()->givesPermission('project.edit'));
    }

    public function test_admin_can_delete_another_account_but_not_their_own_account(): void
    {
        $admin = $this->admin();
        $target = $this->user(['email' => 'delete-target@example.test']);

        $this->get(route('admins.index'))->assertOk()
            ->assertSee('action="'.route('admins.destroy', $target).'"', false)
            ->assertDontSee('action="'.route('admins.destroy', $admin).'"', false);

        $this->delete(route('admins.destroy', $target))
            ->assertRedirect(route('admins.index'))
            ->assertSessionHas('message');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        $this->delete(route('admins.destroy', $admin))->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_invalid_admin_input_does_not_create_an_account(): void
    {
        $this->admin();
        $role = Role::create(['name' => 'ProjectManager', 'type' => 'system']);
        $this->post(route('admins.store'), array_merge($this->payload($role), ['password_confirmation' => 'Mismatch']))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'new-admin@example.test']);

        $clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);
        $this->post(route('admins.store'), $this->payload($clientRole))->assertSessionHasErrors('role_id');
        $this->assertDatabaseMissing('users', ['email' => 'new-admin@example.test']);

        $existing = $this->user(['email' => 'existing@example.test']);
        $this->post(route('admins.store'), array_merge($this->payload($role), ['email' => $existing->email]))
            ->assertSessionHasErrors('email');
    }

    public function test_non_admins_and_guests_cannot_manage_internal_accounts(): void
    {
        $this->get(route('admins.index'))->assertRedirect(route('login'));
        $this->postJson(route('admins.store'), [])->assertUnauthorized();
        foreach (['sentinel', 'client'] as $type) {
            $this->actingAs($this->user(['type' => $type]));
            $this->get(route('admins.index'))->assertForbidden();
            $this->postJson(route('admins.store'), [])->assertForbidden();
        }
    }

    public function test_user_setup_and_profile_pages_are_removed_and_logout_still_works(): void
    {
        $this->admin();
        $this->get(route('projects.index'))->assertOk()->assertDontSee('User Setup')
            ->assertDontSee('user-setup-tab')->assertDontSee('href="contacts-profile"', false)
            ->assertDontSee('change-password')->assertSee('Logout');
        $this->get('/contacts-profile')->assertNotFound();
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'SentinelAdmin'], ['type' => 'system']);
        $user = $this->user();
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'type' => 'sentinel', 'dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg',
        ], $attributes));
    }

    private function payload(Role $role): array
    {
        return [
            'name' => 'New Admin', 'email' => 'new-admin@example.test',
            'password' => 'ExamplePassword123', 'password_confirmation' => 'ExamplePassword123',
            'role_id' => $role->id, 'status' => 'active', '_admin_form' => 'create',
        ];
    }
}
