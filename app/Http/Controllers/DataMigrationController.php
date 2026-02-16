<?php

namespace App\Http\Controllers;

use App\Models\WaterLevelSensor;
use App\Models\WaterLevelSensorData;
use App\Models\WeatherStation;
use App\Models\WeatherStationObservationData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DataMigrationController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $weatherStations = WeatherStation::all();
        $waterLevelSensors = WaterLevelSensor::all();

        return Inertia::render('LFEWS/DataMigration', [
            'weatherStations' => $weatherStations,
            'waterLevelSensors' => $waterLevelSensors,
        ]);
    }

    public function import(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $request->validate([
            'target' => 'required|in:weather_station,water_level_sensor',
            'target_id' => 'required|integer',
            'rows' => 'required|array',
        ]);

        $target = $request->input('target');
        $targetId = $request->input('target_id');
        $data = $request->input('rows');

        if ($target === 'weather_station') {
            foreach ($data as $row) {
                WeatherStationObservationData::create(array_merge($row, [
                    'weather_station_id' => $targetId,
                ]));
            }
        } elseif ($target === 'water_level_sensor') {
            foreach ($data as $row) {
                WeatherStationObservationData::create(array_merge($row, [
                    'water_level_sensor_id' => $targetId,
                ]));
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data imported successfully.']);
        }

        return back()->with('success', 'Data imported successfully.');
    }
}
