<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\WaterLevelSensor;
use App\Models\Location;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $sensors = WaterLevelSensor::with('location.locationType')->get();
        $locations = Location::with('locationType')->get();
        
        return Inertia::render('Reports', [
            'sensors' => $sensors,
            'locations' => $locations,
            'activeCount' => WaterLevelSensor::where('state', 1)->count(),
            'inactiveCount' => WaterLevelSensor::where('state', 0)->count(),
            'maintenanceCount' => WaterLevelSensor::where('state', '!=', 1)->where('state', '!=', 0)->count(),
        ]);
        
    }

    public function 
}
