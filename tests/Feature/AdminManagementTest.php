<?php

namespace Tests\Feature;

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

    public function test_admin_management_shows_database_users_with_the_grid_before_the_add_modal(): void
    {
        $this->admin();
        $user = $this->user(['name' => 'Database User', 'email' => 'real-user@example.test']);
        $response = $this->get(route('admins.index'))->assertOk()
            ->assertSee($user->name)->assertSee($user->email)
            ->assertSee('Tambah Admin')->assertSee('id="admin-user-grid"', false)
            ->assertDontSee('ops@resq.id')->assertDontSee('User Setup')
            ->assertDontSee('href="contacts-profile"', false)
            ->assertDontSee('change-password')->assertSee('Logout');

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="admin-user-grid"'), strpos($html, 'data-bs-target="#add-admin-modal"'));
        $this->assertLessThan(strpos($html, 'id="add-admin-modal"'), strpos($html, 'id="admin-user-grid"'));
        $this->get(route('admins.index', ['q' => 'real-user']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $user->id);
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
