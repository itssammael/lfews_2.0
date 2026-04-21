<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaterLevelSensorData;
use App\Models\WeatherStationObservationData;

class DataApiController extends Controller
{
    /**
     * Get filtered data from a specified data source, grouped by sensor or station.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilteredData(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'data_source' => 'required|string|in:weather_observation_data,water_level_sensor_data',
        ]);

        $startDate = $request->start_date . ' 00:00:00';
        $endDate = $request->end_date . ' 23:59:59';

        if ($request->data_source === 'water_level_sensor_data') {
            $data = WaterLevelSensorData::with('sensor')
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(fn($item) => $item->sensor->name)
                ->map(fn($group) => $group->map(fn($item) => [
                    'water_level' => (float) $item->sensor_data,
                    'date_time' => $item->date,
                ]));
        } else {
            $data = WeatherStationObservationData::with('weatherStation')
                ->whereBetween('date_time', [$startDate, $endDate])
                ->get()
                ->groupBy(fn($item) => $item->weatherStation->name)
                ->map(fn($group) => $group->map(fn($item) => [
                    'temperature' => (float) $item->temperature,
                    'heat_index' => (float) $item->heat_index,
                    'dewpoint' => (float) $item->dewpoint,
                    'humidity' => (float) $item->humidity,
                    'precipitation_rate' => (float) $item->precipitation_rate,
                    'precipitation_total' => (float) $item->precipitation_total,
                    'date_time' => $item->date_time,
                ]));
        }

        return response()->json($data);
    }
}
