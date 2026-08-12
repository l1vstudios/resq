<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_code',
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'max_users',
        'max_projects',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'client_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function roles()
    {
        return $this->morphMany(Role::class, 'model', 'model_type', 'model_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
