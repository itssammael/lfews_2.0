<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tide extends Model
{
    use HasFactory;

    protected $fillable = [
        'dt',
        'date',
        'height',
        'type',
        'latitude',
        'longitude',
        'station',
    ];
}
