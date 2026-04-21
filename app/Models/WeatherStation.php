<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherStation extends Model
{
    protected $fillable = [
        'name',
        'station_id',
        'mode',
        'key',
        'ip',
        'state',
        'location_id',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // public function observations()
    // {
    //     return $this->hasMany(WeatherStationObservationData::class);
    // }

    public function latestObservation()
    {
        return $this->hasOne(WeatherStationObservationData::class)->latestOfMany('date_time');
    }
}
