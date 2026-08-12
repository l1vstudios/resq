<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'type',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles')
            ->withTimestamps();
    }

    public function clients()
    {
        return $this->morphedByMany(Client::class, 'model', 'model_has_roles')
            ->withTimestamps();
    }

    public function givesPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
