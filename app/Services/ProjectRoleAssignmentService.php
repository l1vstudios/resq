<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Single write path for roles that are limited to a client project.
 *
 * Controllers used by a Super Admin should call this service instead of
 * attaching rows to user_has_project_roles directly.
 */
class ProjectRoleAssignmentService
{
    public function assign(User $user, Project $project, Role $role, ?User $assignedBy = null): void
    {
        if (! $role->is_assignable) {
            throw ValidationException::withMessages([
                'role' => 'Role ini tidak dapat ditugaskan.',
            ]);
        }

        if ($user->isClientUser() && (int) $user->client_id !== (int) $project->client_id) {
            throw ValidationException::withMessages([
                'project_id' => 'Project harus milik client yang sama dengan user.',
            ]);
        }

        if ($role->client_id !== null && (int) $role->client_id !== (int) $project->client_id) {
            throw ValidationException::withMessages([
                'role' => 'Role hanya dapat digunakan pada project milik client pemilik role.',
            ]);
        }

        // Keep the project selection and scoped role assignment in sync.
        $user->projects()->syncWithoutDetaching([
            $project->id => ['access_level' => 'viewer'],
        ]);

        $user->projectRoles()->syncWithoutDetaching([
            $role->id => ['project_id' => $project->id, 'assigned_by' => $assignedBy?->id],
        ]);
    }

    public function revoke(User $user, Project $project, Role $role): void
    {
        $user->projectRoles()
            ->wherePivot('project_id', $project->id)
            ->detach($role->id);
    }
}
