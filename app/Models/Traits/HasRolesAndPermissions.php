<?php

namespace App\Models\Traits;

use App\Models\Role;
use App\Models\Permission;

trait HasRolesAndPermissions
{
    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles')
            ->withTimestamps();
    }

    public function assignRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function removeRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->roles()->whereIn('name', $roles)->exists();
        }

        return $this->roles()->where('name', $roles)->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        // First check directly assigned permissions if any, otherwise check via roles
        foreach ($this->roles as $role) {
            if ($role->givesPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
