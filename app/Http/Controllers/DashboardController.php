<?php

namespace App\Http\Controllers;

use App\Services\ModbusService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {

        return Inertia::render('Dashboard', [
            'sensors' => \App\Models\WaterLevelSensor::all(),
            'stations' => \App\Models\WeatherStation::all(),
            'latestData' => \Illuminate\Support\Facades\Cache::get('latest_modbus_data'),
            'historyData' => \Illuminate\Support\Facades\Cache::get('modbus_history', []),
            'latestWeatherData' => \Illuminate\Support\Facades\Cache::get('latest_weather_observation_data'),
            'historyWeatherData' => \Illuminate\Support\Facades\Cache::get('weather_observation_history', []),
        ]);
    }

    public function pullWeatherData()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-data');

        $weatherStationController = new WeatherStationController();
        $results = $weatherStationController->pullObservationData();

        return redirect()->route('dashboard')->with('weatherResult', $results);
    }

    public function pullWaterData(ModbusService $modbusService)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-data');
        $waterLevelSensorController = new WaterLevelSensorController();
        $results = $waterLevelSensorController->pullWaterData($modbusService);

        return redirect()->route('dashboard')->with('modbusResult', $results);
    }
}
