<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterLevelSensorData extends Model
{
    protected $fillable = [
        'date',
        'sensor_data',
        'water_level_sensor_id',
    ];

    public function sensor()
    {
        return $this->belongsTo(WaterLevelSensor::class, 'water_level_sensor_id');
    }
}
