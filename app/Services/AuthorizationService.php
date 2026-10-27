<?php

namespace App\Services;

//aa
use App\Models\CorridorMonitoring;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\ReferencePoint;
use App\Models\ReferenceRoute;
use App\Models\Sensor;
use App\Models\SpatialInformationLayer;
use App\Models\StationSpatialReference;
use App\Models\User;
use App\Models\WarningStation;

class AuthorizationService
{
    /**
     * Check if user can access a project. Protects against IDOR.
     */
    public function canAccessProject(User $user, Project|int $project): bool
    {
        return $user->canAccessProject($project);
    }

    /**
     * Check if user can edit/mutate a project. Requires explicit permission.
     */
    public function canEditProject(User $user, Project $project): bool
    {
        if (! $this->canAccessProject($user, $project)) {
            return false;
        }

        return $user->hasPermissionForProject('project.edit', $project);
    }

    /**
     * Check if user can create projects. Sentinel-only.
     */
    public function canCreateProject(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->isSentinel()) {
            return false;
        }

        return $user->hasPermissionTo('project.create');
    }

    /**
     * Check if user can delete projects. Sentinel-only, explicit permission.
     */
    public function canDeleteProject(User $user, Project $project): bool
    {
        if (! $this->canAccessProject($user, $project)) {
            return false;
        }

        return $user->hasPermissionForProject('project.delete', $project);
    }

    public function canCreateSpatialResource(User $user, Project $project): bool
    {
        return $this->canAccessProject($user, $project)
            && $user->hasPermissionForProject('project.create', $project);
    }

    public function canEditSpatialResource(User $user, Project $project): bool
    {
        return $this->canEditProject($user, $project);
    }

    public function canDeleteSpatialResource(User $user, Project $project): bool
    {
        return $this->canDeleteProject($user, $project);
    }

    public function canConfigureHydrometEws(User $user, Project $project): bool
    {
        if (! $this->canAccessProject($user, $project)) {
            return false;
        }

        // Compatibility for pre-existing client accounts that have not yet
        // been converted to explicit project membership/role assignments.
        // Once a project is selected, CRUD follows the role permission (or
        // the legacy operator/manager membership level), never a role name.
        $membership = $user->projects()->whereKey($project->id)->first();
        $hasProjectRole = $user->projectRoles()
            ->where('user_has_project_roles.project_id', $project->id)
            ->exists();

        if ($user->isClientUser() && ! $membership && ! $hasProjectRole) {
            return true;
        }

        if ($membership && in_array($membership->pivot->access_level, ['operator', 'manager'], true)) {
            return true;
        }

        return $user->hasPermissionForProject('project.edit', $project)
            || $user->hasPermissionForProject('project.create', $project);
    }

    public function canAccessOperationalState(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSentinel() && $user->hasPermissionTo('operational-state.access'));
    }

    public function canAccessOperationalIntegrity(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSentinel() && $user->hasPermissionTo('operational-integrity.access'));
    }

    public function canAccessAdministrativeMonitoring(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSentinel() && $user->hasPermissionTo('administrative-monitoring.access'));
    }

    public function canAccessPlatformOperations(User $user): bool
    {
        return $this->canAccessOperationalState($user)
            || $this->canAccessOperationalIntegrity($user)
            || $this->canAccessAdministrativeMonitoring($user);
    }

    public function canAccessReporting(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSentinel() && $user->hasPermissionTo('reporting.access'));
    }

    public function canMutateAssetRegistry(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSentinel();
    }

    /**
     * Check if user can access workspace within their authorized project.
     */
    public function canAccessWorkspace(User $user, GeospatialWorkspace $workspace): bool
    {
        return $this->canAccessProject($user, $workspace->project_id);
    }

    /**
     * Check if user can access monitoring station within their authorized project.
     */
    public function canAccessMonitoringStation(User $user, MonitoringStation $station): bool
    {
        $projectId = $station->project_id ?: $station->workspace?->project_id;

        return $projectId && $this->canAccessProject($user, $projectId);
    }

    /**
     * Check if user can access warning station within their authorized project.
     */
    public function canAccessWarningStation(User $user, WarningStation $station): bool
    {
        $projectId = $station->project_id ?: $station->workspace?->project_id;

        return $projectId && $this->canAccessProject($user, $projectId);
    }

    /**
     * Check if user can access sensor within their authorized project.
     */
    public function canAccessSensor(User $user, Sensor $sensor): bool
    {
        $workspace = $sensor->workspace;

        return $workspace && $this->canAccessProject($user, $workspace->project_id);
    }

    public function canAccessInformationLayer(User $user, SpatialInformationLayer $layer): bool
    {
        return $this->canAccessProject($user, $layer->project_id);
    }

    public function canAccessCorridor(User $user, CorridorMonitoring $corridor): bool
    {
        return $this->canAccessProject($user, $corridor->project_id);
    }

    public function canAccessReferenceRoute(User $user, ReferenceRoute $route): bool
    {
        return $this->canAccessProject($user, $route->project_id);
    }

    public function canAccessReferencePoint(User $user, ReferencePoint $point): bool
    {
        return $this->canAccessProject($user, $point->project_id);
    }

    public function canAccessStationSpatialReference(User $user, StationSpatialReference $reference): bool
    {
        return $this->canAccessProject($user, $reference->project_id);
    }

    /**
     * Scope a query to only projects the user can access.
     * For Sentinel: all projects (depends on permission).
     * For Client users: only projects within their client.
     */
    public function scopeProjectsForUser(User $user, $query)
    {
        if (! $user->isActive()) {
            return $query->whereRaw('1=0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $selectedProjectIds = $user->selectedProjectIds();
        if ($selectedProjectIds !== []) {
            return $query->whereIn('resq_projects.id', $selectedProjectIds);
        }

        if ($user->isSentinel()) {
            return $query;
        }

        if ($user->isClientUser()) {
            return $query->where('client_id', $user->client_id);
        }

        return $query->whereRaw('1=0'); // No access
    }

    /**
     * Scope workspaces to user's accessible projects.
     */
    public function scopeWorkspacesForUser(User $user, $query)
    {
        return $query->whereHas('project', function ($projects) use ($user) {
            $this->scopeProjectsForUser($user, $projects);
        });
    }

    public function scopeProjectSpatialResourcesForUser(User $user, $query)
    {
        return $query->whereHas('project', function ($projects) use ($user) {
            $this->scopeProjectsForUser($user, $projects);
        });
    }
}
