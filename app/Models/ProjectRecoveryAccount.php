<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectRecoveryAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'user_id',
        'recovery_username',
        'recovery_password_hash',
        'status',
        'last_used_at',
        'notes',
    ];

    protected $hidden = [
        'recovery_password_hash',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUnused(): bool
    {
        return $this->status === 'unused';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }
}
