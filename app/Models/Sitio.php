<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    protected $fillable = [
        'name',
        'barangay_name',
        'barangay_id',
        'properties',
        'geometry',
    ];

    protected $casts = [
        'properties' => 'array',
        'geometry' => 'array',
    ];
}
