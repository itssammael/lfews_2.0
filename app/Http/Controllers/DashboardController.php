<?php

namespace App\Http\Controllers;

use App\Services\ModbusService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');

        $stations = \App\Models\WeatherStation::all();
        $historyWeatherData = [];

        foreach ($stations as $station) {
            $latestEntry = \App\Models\WeatherStationObservationData::where('weather_station_id', $station->id)
                ->orderBy('date_time', 'desc')
                ->first();

            if ($latestEntry) {
                $targetDate = \Illuminate\Support\Carbon::parse($latestEntry->date_time)->toDateString();
                $historyWeatherData[$station->id] = \App\Models\WeatherStationObservationData::where('weather_station_id', $station->id)
                    ->whereDate('date_time', $targetDate)
                    ->orderBy('date_time', 'asc')
                    ->get()
                    ->map(fn($entry) => [
                        'data' => [
                            'precipitation_rate' => $entry->precipitation_rate,
                            'precipitation_total' => $entry->precipitation_total,
                            'temperature' => $entry->temperature,
                            'humidity' => $entry->humidity,
                            'wind_speed' => $entry->wind_speed,
                        ],
                        'timestamp' => $entry->date_time,
                    ])->toArray();
            }
        }

        return Inertia::render('LFEWS/Dashboard', [
            'sensors' => \App\Models\WaterLevelSensor::all(),
            'stations' => $stations,
            'latestData' => \Illuminate\Support\Facades\Cache::get('latest_modbus_data'),
            'historyData' => \Illuminate\Support\Facades\Cache::get('modbus_history', []),
            'latestWeatherData' => \Illuminate\Support\Facades\Cache::get('latest_weather_observation_data'),
            'historyWeatherData' => $historyWeatherData,
        ]);
    }

    public function pullWeatherData()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');

        $weatherStationController = new WeatherStationController();
        $results = $weatherStationController->pullObservationData();

        return redirect()->route('dashboard')->with('weatherResult', $results);
    }

    public function pullWaterData(ModbusService $modbusService)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $waterLevelSensorController = new WaterLevelSensorController();
        $results = $waterLevelSensorController->pullWaterData($modbusService);

        return redirect()->route('dashboard')->with('modbusResult', $results);
    }

    public function refreshWaterData()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');

        $sensors = \App\Models\WaterLevelSensor::where('state', 1)->get();
        $results = [];
        $history = [];

        foreach ($sensors as $sensor) {
            $latestEntry = \App\Models\WaterLevelSensorData::where('water_level_sensor_id', $sensor->id)
                ->orderBy('date', 'desc')
                ->first();

            if ($latestEntry) {
                $results[$sensor->id] = [
                    'sensor_id' => $sensor->id,
                    'name' => $sensor->name,
                    'success' => true,
                    'data' => $latestEntry->sensor_data,
                    'timestamp' => $latestEntry->date,
                ];
            }

            $last50Entries = \App\Models\WaterLevelSensorData::where('water_level_sensor_id', $sensor->id)
                ->orderBy('date', 'desc')
                ->limit(50)
                ->get()
                ->reverse()
                ->map(fn($entry) => [
                    'value' => $entry->sensor_data,
                    'timestamp' => $entry->date,
                ])->values()->toArray();

            $history[$sensor->id] = $last50Entries;
        }

        if (!empty($results)) {
            \Illuminate\Support\Facades\Cache::put('latest_modbus_data', $results, 60);
        }
        if (!empty($history)) {
            \Illuminate\Support\Facades\Cache::put('modbus_history', $history, 60);
        }

        return redirect()->route('dashboard')->with('modbusResult', $results);
    }

    public function refreshWeatherData()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');

        $stations = \App\Models\WeatherStation::where('state', 1)->get();
        $observations = [];
        $history = \Illuminate\Support\Facades\Cache::get('weather_observation_history', []);

        foreach ($stations as $station) {
            $latestEntry = \App\Models\WeatherStationObservationData::where('weather_station_id', $station->id)
                ->orderBy('date_time', 'desc')
                ->first();

            if ($latestEntry) {
                $observations[$station->id] = [
                    'id' => $station->id,
                    'station_id' => $station->station_id,
                    'name' => $station->name,
                    'success' => true,
                    'data' => [
                        'temperature' => $latestEntry->temperature,
                        'heat_index' => $latestEntry->heat_index,
                        'dewpoint' => $latestEntry->dewpoint,
                        'humidity' => $latestEntry->humidity,
                        'wind_speed' => $latestEntry->wind_speed,
                        'wind_direction' => $latestEntry->wind_direction,
                        'wind_gust' => $latestEntry->wind_gust,
                        'pressure' => $latestEntry->pressure,
                        'precipitation_rate' => $latestEntry->precipitation_rate,
                        'precipitation_total' => $latestEntry->precipitation_total,
                        'uv' => $latestEntry->uv,
                        'solar_radiation' => $latestEntry->solar_radiation,
                        'date_time' => $latestEntry->date_time,
                    ],
                    'timestamp' => $latestEntry->created_at->toDateTimeString(),
                ];

                $targetDate = \Illuminate\Support\Carbon::parse($latestEntry->date_time)->toDateString();
                $history[$station->id] = \App\Models\WeatherStationObservationData::where('weather_station_id', $station->id)
                    ->whereDate('date_time', $targetDate)
                    ->orderBy('date_time', 'asc')
                    ->get()
                    ->map(fn($entry) => [
                        'data' => [
                            'precipitation_rate' => $entry->precipitation_rate,
                            'precipitation_total' => $entry->precipitation_total,
                            'temperature' => $entry->temperature,
                            'humidity' => $entry->humidity,
                            'wind_speed' => $entry->wind_speed,
                        ],
                        'timestamp' => $entry->date_time,
                    ])->toArray();
            }
        }

        if (!empty($observations)) {
            \Illuminate\Support\Facades\Cache::put('latest_weather_observation_data', $observations, 60);
        }
        if (!empty($history)) {
            \Illuminate\Support\Facades\Cache::put('weather_observation_history', $history, 60);
        }

        return redirect()->route('dashboard')->with('weatherResult', $observations);
    }
}
