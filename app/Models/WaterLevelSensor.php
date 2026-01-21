<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterLevelSensor extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'model',
        'lat',
        'long',
        'location',
        'level_2',
        'level_3',
        'level_4',
        'status',
        'state',
        'ip',
        'port',
        'slave_id',
    ];
}
