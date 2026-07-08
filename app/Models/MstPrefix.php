<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstPrefix extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefix_code',
        'name',
        'description',
        'status',
    ];

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }
}
