<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TideHeight extends Model
{
    protected $fillable = ['dt', 'date', 'height', 'latitude', 'longitude'];
}
