<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientUserManagementController extends Controller
{
    private const MAX_OPERATIONAL_USERS = 5;

    public function __construct(
        private readonly AuthorizationService $authorization
    ) {}

    public function index(Request $request): View
    {
        $client = $this->authorizeClientAdmin($request);

        return view('modules.client-operations.users', [
            'client' => $client,
            'users' => $this->clientUsers($client)->with('projects')->orderBy('name')->get(),
            'projects' => $client->projects()->orderBy('project_code')->get(),
            'roles' => Role::whereIn('name', ['ClientAdmin', 'ClientOperator', 'ClientViewer'])->orderBy('name')->get(),
            'operationalUserCount' => $this->operationalUserCount($client),
            'maxOperationalUsers' => self::MAX_OPERATIONAL_USERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = $this->authorizeClientAdmin($request);
        abort_if($this->operationalUserCount($client) >= self::MAX_OPERATIONAL_USERS, 422, 'Client operational user limit reached.');

        $data = $this->validatedUser($request);
        $role = Role::where('name', $data['role'])->firstOrFail();
        $projectIds = $this->validatedProjectIds($client, $data['project_ids'] ?? []);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'dob' => $data['dob'] ?? '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $client->id,
            'status' => $data['status'],
        ]);
        $user->assignRole($role);
        $this->syncProjects($user, $projectIds, $data['access_level']);

        return back()->with('message', 'Client user created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $client = $this->authorizeClientAdmin($request);
        $this->authorizeClientUser($client, $user);

        $data = $this->validatedUser($request, $user);
        $role = Role::where('name', $data['role'])->firstOrFail();
        $projectIds = $this->validatedProjectIds($client, $data['project_ids'] ?? []);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->roles()->sync([$role->id]);
        $this->syncProjects($user, $projectIds, $data['access_level']);

        return back()->with('message', 'Client user updated.');
    }

    private function authorizeClientAdmin(Request $request): Client
    {
        $user = $request->user();
        abort_unless($user?->isClientUser() && $user->hasRole('ClientAdmin') && $user->client, 403);

        return $user->client;
    }

    private function authorizeClientUser(Client $client, User $user): void
    {
        abort_unless($user->isClientUser() && (int) $user->client_id === (int) $client->id, 403);
    }

    private function clientUsers(Client $client)
    {
        return User::query()
            ->where('type', 'client')
            ->where('client_id', $client->id);
    }

    private function operationalUserCount(Client $client): int
    {
        return $this->clientUsers($client)->count();
    }

    private function validatedUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'status' => ['required', Rule::in(['active', 'suspended', 'inactive'])],
            'role' => ['required', Rule::in(['ClientAdmin', 'ClientOperator', 'ClientViewer'])],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer'],
            'access_level' => ['required', Rule::in(['viewer', 'operator', 'manager'])],
            'dob' => ['nullable', 'date'],
        ]);
    }

    private function validatedProjectIds(Client $client, array $projectIds): array
    {
        $allowed = Project::where('client_id', $client->id)->whereIn('id', $projectIds)->pluck('id')->all();
        abort_if(count($allowed) !== count(array_unique($projectIds)), 403);

        return $allowed;
    }

    private function syncProjects(User $user, array $projectIds, string $accessLevel): void
    {
        $user->projects()->sync(collect($projectIds)->mapWithKeys(fn ($id) => [
            $id => ['access_level' => $accessLevel],
        ])->all());
    }
}
