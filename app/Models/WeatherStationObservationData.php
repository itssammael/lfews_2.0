<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherStationObservationData extends Model
{
    protected $fillable = [
        'temperature',
        'heat_index',
        'dewpoint',
        'humidity',
        'wind_speed',
        'wind_direction',
        'wind_gust',
        'pressure',
        'precipitation_rate',
        'precipitation_total',
        'uv',
        'solar_radiation',
        'weather_station_id',
        'date_time',
    ];

    public function weatherStation()
    {
        return $this->belongsTo(WeatherStation::class);
    }
}
