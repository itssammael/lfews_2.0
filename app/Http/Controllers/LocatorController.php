<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Location;
use App\Models\WaterLevelSensor;
use App\Models\WeatherStation;
use Inertia\Inertia;

class LocatorController extends Controller
{
    public function index()
    {
        $weatherStations = WeatherStation::with('location')->get();
        $waterLevelSensors = WaterLevelSensor::with('location')->get();
        $locations = Location::with('locationType')->whereHas('locationType')->get();
        $rivers = \App\Models\River::all();
        $contours = \App\Models\Contour::all();
        $floodRisks = \App\Models\FloodRisk::all();
        
        return Inertia::render('LFEWS/Locator', [
            'weatherStations' => $weatherStations,
            'waterLevelSensors' => $waterLevelSensors,
            'locations' => $locations,
            'rivers' => $rivers,
            'contours' => $contours,
            'floodRisks' => $floodRisks,
        ]);
    }
}
