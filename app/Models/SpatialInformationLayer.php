<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpatialInformationLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'layer_code',
        'name',
        'layer_type',
        'source_url',
        'layer_payload',
        'style_color',
        'visible_by_default',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'layer_payload' => 'array',
        'visible_by_default' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace()
    {
        return $this->belongsTo(GeospatialWorkspace::class, 'workspace_id');
    }
}
