<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FloodRisk extends Model
{
    protected $fillable = [
        'name',
        'properties',
        'geometry',
    ];

    protected $casts = [
        'properties' => 'array',
        'geometry' => 'array',
    ];
}
