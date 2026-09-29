<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TdeMatrixVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'matrix_code',
        'name',
        'version_label',
        'source_filename',
        'matrix_rows',
        'import_summary',
        'imported_by_user_id',
        'status',
    ];

    protected $casts = [
        'matrix_rows' => 'array',
        'import_summary' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }
}
