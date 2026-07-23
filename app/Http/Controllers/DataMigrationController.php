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
            $numericFields = [
                'temperature',
                'heat_index',
                'dewpoint',
                'humidity',
                'wind_speed',
                'wind_direction',
                'wind_gust',
                'pressure',
                'precipitation_rate',
                'precipitation_total',
                'uv',
                'solar_radiation',
            ];
            $nullableFields = ['uv', 'solar_radiation'];

            foreach ($data as $row) {
                // Build a case-insensitive normalized row key mapping
                $normalized = [];
                foreach ($row as $key => $val) {
                    $normalized[strtolower(trim($key))] = $val;
                }

                $dateTimeVal = $normalized['date_time']
                    ?? $normalized['date']
                    ?? $normalized['datetime']
                    ?? $normalized['timestamp']
                    ?? now()->toDateTimeString();

                $cleanedRow = [
                    'weather_station_id' => $targetId,
                    'date_time' => $dateTimeVal,
                ];

                foreach ($numericFields as $field) {
                    if (array_key_exists($field, $normalized)) {
                        $val = $normalized[$field];
                        $default = in_array($field, $nullableFields) ? null : 0;
                        $cleanedRow[$field] = $this->cleanNumeric($val, $default);
                    } else {
                        if (!in_array($field, $nullableFields)) {
                            $cleanedRow[$field] = 0;
                        }
                    }
                }

                WeatherStationObservationData::create($cleanedRow);
            }
        } elseif ($target === 'water_level_sensor') {
            foreach ($data as $row) {
                $normalized = [];
                foreach ($row as $key => $val) {
                    $normalized[strtolower(trim($key))] = $val;
                }

                $dateVal = $normalized['date']
                    ?? $normalized['date_time']
                    ?? $normalized['datetime']
                    ?? $normalized['timestamp']
                    ?? now()->toDateTimeString();

                $sensorVal = $normalized['sensor_data']
                    ?? $normalized['water_level']
                    ?? $normalized['level']
                    ?? $normalized['value']
                    ?? 0;

                WaterLevelSensorData::create([
                    'water_level_sensor_id' => $targetId,
                    'date' => $dateVal,
                    'sensor_data' => $this->cleanNumeric($sensorVal, 0),
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data imported successfully.']);
        }

        return back()->with('success', 'Data imported successfully.');
    }

    private function cleanNumeric($value, $default = 0)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_numeric($value)) {
            return $value + 0;
        }
        if (is_string($value)) {
            $cleaned = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value)));
            if ($cleaned !== '' && is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }
        return $default;
    }
}

