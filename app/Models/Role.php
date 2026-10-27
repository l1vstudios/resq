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
        'client_id',
        'is_super_admin',
        'is_assignable',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'is_assignable' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

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

    public function menus()
    {
        return $this->belongsToMany(NavigationMenu::class, 'role_has_menu_access')
            ->withPivot('can_view')
            ->withTimestamps();
    }

    public function projectUsers()
    {
        return $this->belongsToMany(User::class, 'user_has_project_roles')
            ->withPivot(['project_id', 'assigned_by'])
            ->withTimestamps();
    }

    public function givesPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
