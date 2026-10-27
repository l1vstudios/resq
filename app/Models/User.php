<?php

namespace App\Models;

use App\Models\Traits\HasRolesAndPermissions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRolesAndPermissions, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'dob',
        'avatar',
        'type',
        'client_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'user_has_projects', 'user_id', 'project_id')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    /** Roles which apply only while the user is working in the selected project. */
    public function projectRoles()
    {
        return $this->belongsToMany(Role::class, 'user_has_project_roles')
            ->withPivot(['project_id', 'assigned_by'])
            ->withTimestamps();
    }

    public function recoveryAccounts()
    {
        return $this->hasMany(ProjectRecoveryAccount::class);
    }

    public function sentinelNotifications()
    {
        return $this->hasMany(SentinelNotification::class);
    }

    public function isSentinel(): bool
    {
        return $this->type === 'sentinel';
    }

    public function isClientUser(): bool
    {
        return $this->type === 'client';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user has authority to access a given Project.
     */
    public function canAccessProject(Project|int $project): bool
    {
        $projectId = is_int($project) ? $project : $project->id;
        $projectModel = is_int($project) ? Project::find($project) : $project;

        if (! $projectModel) {
            return false;
        }

        if (! $this->isActive()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Explicit project selection always wins. A user assigned to one
        // project therefore cannot receive map/data from another project.
        $selectedProjectIds = $this->selectedProjectIds();
        if ($selectedProjectIds !== []) {
            return in_array($projectId, $selectedProjectIds, true);
        }

        // Existing Sentinel accounts without project assignments remain
        // platform-wide; Super Admin is no longer identified by a role name.
        if ($this->isSentinel()) {
            return true;
        }

        // Client users without an explicit project selection retain the
        // legacy client-wide scope. New accounts should always be assigned a
        // project through user_has_projects.
        if ($this->isClientUser()) {
            return $this->client_id !== null && $this->client_id === $projectModel->client_id;
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->where('is_super_admin', true)
            ->whereNull('client_id')
            ->exists();
    }

    /** @return array<int, int> */
    public function selectedProjectIds(): array
    {
        $memberships = $this->projects()->pluck('resq_projects.id')->map(fn ($id) => (int) $id);
        $roleAssignments = $this->projectRoles()
            ->pluck('user_has_project_roles.project_id')
            ->map(fn ($id) => (int) $id);

        return $memberships->merge($roleAssignments)->unique()->values()->all();
    }

    public function hasPermissionForProject(string $permission, Project|int $project): bool
    {
        $projectId = is_int($project) ? $project : $project->id;

        if ($this->isSuperAdmin() || $this->hasPermissionTo($permission)) {
            return true;
        }

        return $this->projectRoles()
            ->where('user_has_project_roles.project_id', $projectId)
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    public function canViewMenu(NavigationMenu|int $menu, Project|int|null $project = null): bool
    {
        $menuId = is_int($menu) ? $menu : $menu->id;

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->roles()->whereHas('menus', function ($query) use ($menuId) {
            $query->where('navigation_menus.id', $menuId)->where('role_has_menu_access.can_view', true);
        })->exists()) {
            return true;
        }

        if ($project === null) {
            return false;
        }

        $projectId = is_int($project) ? $project : $project->id;

        return $this->projectRoles()
            ->where('user_has_project_roles.project_id', $projectId)
            ->whereHas('menus', function ($query) use ($menuId) {
                $query->where('navigation_menus.id', $menuId)->where('role_has_menu_access.can_view', true);
            })
            ->exists();
    }
}
