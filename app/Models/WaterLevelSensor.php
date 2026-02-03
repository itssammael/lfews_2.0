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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level_2' => 'float',
            'level_3' => 'float',
            'level_4' => 'float',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
