<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'location_type_id',
    ];

    public function locationType()
    {
        return $this->belongsTo(LocationType::class);
    }
}
