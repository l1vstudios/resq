<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    private const INTERNAL_ROLES = ['SentinelAdmin', 'ProjectManager', 'PlatformOperator'];

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]);
        $search = trim($filters['q'] ?? '');

        return view('modules.admins.index', [
            'users' => User::with(['roles', 'projects', 'client'])
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->whereIn('name', self::INTERNAL_ROLES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'dob' => '2000-01-01', 'avatar' => 'images/avatar-1.jpg',
                'type' => 'sentinel', 'status' => $data['status'], 'email_verified_at' => now(),
            ]);
            $user->roles()->sync([$data['role_id']]);
        });

        return redirect()->route('admins.index')->with('message', 'Admin berhasil ditambahkan.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSentinel() && $request->user()->hasRole('SentinelAdmin'), 403);
    }
}
