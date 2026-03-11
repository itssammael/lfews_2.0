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
        $weatherStations = WeatherStation::with(['location', 'latestObservation'])->get();
        $waterLevelSensors = WaterLevelSensor::with(['location', 'latestData'])->get();
        $locations = Location::with('locationType')->whereHas('locationType')->get();
        $rivers = \App\Models\River::all();
        $floodRisks = \App\Models\FloodRisk::all();
        $barangays = \App\Models\Barangay::all();

        return Inertia::render('LFEWS/Locator', [
            'weatherStations' => $weatherStations,
            'waterLevelSensors' => $waterLevelSensors,
            'locations' => $locations,
            'rivers' => $rivers,
            'floodRisks' => $floodRisks,
            'barangays' => $barangays,
        ]);
    }

    public function apiContours(Request $request)
    {
        return response()->json(\App\Models\Contour::paginate(500));
    }

    public function apiSitios(Request $request)
    {
        return response()->json(\App\Models\Sitio::paginate(500));
    }
}
