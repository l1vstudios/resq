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

        // Sentinel internal users can access according to role/permissions or globally if platform admin
        if ($this->isSentinel()) {
            return true;
        }

        // Client operational users: Must belong to the project's client organization
        if ($this->isClientUser()) {
            if (! $this->client_id || $this->client_id !== $projectModel->client_id) {
                return false;
            }

            // Check if explicitly assigned to project or has client-wide project manager role
            if ($this->hasRole('ClientAdmin')) {
                return true;
            }

            return $this->projects()->where('resq_projects.id', $projectId)->exists();
        }

        return false;
    }
}
