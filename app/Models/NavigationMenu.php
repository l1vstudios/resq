<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'code',
        'label',
        'route_name',
        'resource',
        'sort_order',
        'is_project_scoped',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_project_scoped' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_menu_access')
            ->withPivot('can_view')
            ->withTimestamps();
    }
}
