<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationType extends Model
{
    protected $fillable = [
        'description',
        'has_multiple_dots',
    ];
}
