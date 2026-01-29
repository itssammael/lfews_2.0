<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherStation extends Model
{
    protected $fillable = [
        'name',
        'station_id',
        'mode',
        'state',
        'location_id',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
