<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanonicalProcessingRun extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
}
