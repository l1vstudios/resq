<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'resq_projects';

    protected $fillable = [
        'project_code',
        'name',
        'owner',
        'project_date',
        'status',
    ];

    public function workspaces()
    {
        return $this->hasMany(GeospatialWorkspace::class);
    }
}
