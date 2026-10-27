<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\NavigationMenu;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\ProjectRoleAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    private const INTERNAL_ROLES = ['SentinelAdmin', 'ProjectManager', 'PlatformOperator'];

    private const ACCESS_ACTIONS = ['view', 'create', 'edit', 'delete'];

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]);
        $search = trim($filters['q'] ?? '');

        return view('modules.admins.index', [
            'users' => User::with(['roles', 'projectRoles', 'projects', 'client'])
                ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->whereLike('name', '%'.$search.'%')->orWhereLike('email', '%'.$search.'%')))
                ->orderBy('name')->orderBy('id')->paginate(10)->appends($filters),
            'roles' => Role::whereIn('name', self::INTERNAL_ROLES)->orderBy('display_name')->get(),
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        // Keeps the original POST endpoint backward-compatible for API/form
        // consumers while the web UI now submits the full RBAC page.
        $request->mergeIfMissing(['account_type' => 'sentinel', 'role_mode' => 'existing']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'account_type' => ['required', Rule::in(['sentinel', 'client'])],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', Rule::exists('resq_projects', 'id')],
            'role_mode' => ['nullable', Rule::in(['existing', 'new'])],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->whereIn('name', self::INTERNAL_ROLES)],
            'role_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('roles', 'name')],
            'role_display_name' => ['nullable', 'string', 'max:255'],
            'full_access' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => [Rule::in(self::ACCESS_ACTIONS)],
            'menu_ids' => ['nullable', 'array'],
            'menu_ids.*' => ['integer', Rule::exists('navigation_menus', 'id')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $isNewRole = ($data['role_mode'] ?? 'existing') === 'new';
        if ($isNewRole && blank($data['role_name'] ?? null)) {
            return back()->withInput()->withErrors(['role_name' => 'Kode role wajib diisi untuk role baru.']);
        }

        if (! $isNewRole && empty($data['role_id'])) {
            return back()->withInput()->withErrors(['role_id' => 'Pilih role yang digunakan.']);
        }

        if ($data['account_type'] === 'client' && empty($data['client_id'])) {
            return back()->withInput()->withErrors(['client_id' => 'Client wajib dipilih untuk akun client.']);
        }

        $projects = Project::query()->whereIn('id', $data['project_ids'] ?? [])->get();
        if ($data['account_type'] === 'client' && $projects->count() !== count($data['project_ids'] ?? [])) {
            return back()->withInput()->withErrors(['project_ids' => 'Project yang dipilih tidak valid.']);
        }

        if (($data['client_id'] ?? null) && $projects->contains(fn (Project $project) => (int) $project->client_id !== (int) $data['client_id'])) {
            return back()->withInput()->withErrors(['project_ids' => 'Semua project harus milik client yang dipilih.']);
        }

        DB::transaction(function () use ($data, $isNewRole, $projects, $request) {
            $role = $isNewRole
                ? Role::create([
                    'name' => $data['role_name'],
                    'display_name' => $data['role_display_name'] ?: $data['role_name'],
                    'type' => 'custom',
                    'client_id' => $data['client_id'] ?? null,
                    // Full CRUD within selected projects is still project-scoped.
                    'is_super_admin' => (bool) ($data['full_access'] ?? false)
                        && $data['account_type'] === 'sentinel'
                        && $projects->isEmpty(),
                ])
                : Role::findOrFail($data['role_id']);

            if ($isNewRole) {
                $this->syncRoleAccess($role, $data);
            }

            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg',
                'type' => $data['account_type'],
                'client_id' => $data['account_type'] === 'client' ? $data['client_id'] ?? null : null,
                'status' => $data['status'], 'email_verified_at' => now(),
            ]);

            if ($projects->isEmpty()) {
                $user->roles()->sync([$role->id]);

                return;
            }

            $assignments = app(ProjectRoleAssignmentService::class);
            foreach ($projects as $project) {
                $assignments->assign($user, $project, $role, $request->user());
            }
        });

        return redirect()->route('admins.index')->with('message', 'Admin berhasil ditambahkan.');
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('modules.admins.create', [
            'roles' => Role::whereIn('name', self::INTERNAL_ROLES)->where('is_assignable', true)->orderBy('display_name')->get(),
            'clients' => Client::query()->where('status', 'active')->orderBy('name')->get(),
            'projects' => Project::query()->with('client')->orderBy('project_code')->get(),
            'menus' => NavigationMenu::query()->where('is_active', true)->orderBy('sort_order')->orderBy('label')->get(),
            'actions' => self::ACCESS_ACTIONS,
        ]);
    }

    private function syncRoleAccess(Role $role, array $data): void
    {
        $menus = NavigationMenu::query()->where('is_active', true)->get();
        $menuIds = collect($data['menu_ids'] ?? [])->map(fn ($id) => (int) $id);
        $permissionPairs = collect($data['permissions'] ?? [])
            ->flatMap(fn (array $actions, string $resource) => collect($actions)
                ->map(fn (string $action) => ['resource' => $resource, 'action' => $action]))
            ->values();

        if ((bool) ($data['full_access'] ?? false)) {
            $menuIds = $menus->pluck('id');
            $permissionPairs = $menus->pluck('resource')->filter()
                ->push('project')
                ->unique()
                ->flatMap(fn (string $resource) => collect(self::ACCESS_ACTIONS)
                    ->map(fn (string $action) => ['resource' => $resource, 'action' => $action]));
        }

        $permissionIds = $permissionPairs
            ->filter(fn (array $pair) => filled($pair['resource']))
            ->map(function (array $pair) {
                return Permission::firstOrCreate(
                    ['name' => Permission::buildName($pair['resource'], $pair['action'])],
                    [
                        'display_name' => ucfirst($pair['action']).' '.str_replace('-', ' ', $pair['resource']),
                        'resource' => $pair['resource'],
                        'action' => $pair['action'],
                    ]
                )->id;
            });

        $role->permissions()->sync($permissionIds->all());
        $role->menus()->sync($menuIds->mapWithKeys(fn ($id) => [(int) $id => ['can_view' => true]])->all());
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->isSentinel()
                && ($request->user()->isSuperAdmin() || $request->user()->hasRole('SentinelAdmin')),
            403
        );
    }
}
