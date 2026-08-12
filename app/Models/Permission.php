<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'resource',
        'action',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions')
            ->withTimestamps();
    }

    /**
     * Generate a permission name from resource and action.
     */
    public static function buildName(string $resource, string $action): string
    {
        return "{$resource}.{$action}";
    }
}
