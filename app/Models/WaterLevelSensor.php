<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterLevelSensor extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'mode',
        'level_2',
        'level_3',
        'level_4',
        'state',
        'ip',
        'port',
        'slave_id',
        'location_id',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
