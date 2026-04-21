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
            'data_source' => 'required|string|in:weather_station_observation_data,water_level_sensor_data',
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
            // Handles both 'weather_observation_data' and 'weather_station_observation_data'
            $data = WeatherStationObservationData::join('weather_stations', 'weather_station_observation_data.weather_station_id', '=', 'weather_stations.id')
                ->whereBetween('weather_station_observation_data.date_time', [$startDate, $endDate])
                ->select([
                    'weather_stations.name as station_name',
                    'weather_station_observation_data.temperature',
                    'weather_station_observation_data.heat_index',
                    'weather_station_observation_data.dewpoint',
                    'weather_station_observation_data.humidity',
                    'weather_station_observation_data.wind_speed',
                    'weather_station_observation_data.wind_direction',
                    'weather_station_observation_data.wind_gust',
                    'weather_station_observation_data.pressure',
                    'weather_station_observation_data.precipitation_rate',
                    'weather_station_observation_data.precipitation_total',
                    'weather_station_observation_data.date_time'
                ])
                ->orderBy('weather_station_observation_data.date_time', 'asc')
                ->get()
                ->groupBy('station_name')
                ->map(fn($group) => $group->map(fn($item) => [
                    'temperature' => (float) $item->temperature,
                    'heat_index' => (float) $item->heat_index,
                    'dewpoint' => (float) $item->dewpoint,
                    'wind_speed' => (float) $item->wind_speed,
                    'wind_direction' => (float) $item->wind_direction,
                    'wind_gust' => (float) $item->wind_gust,
                    'pressure' => (float) $item->pressure,
                    'humidity' => (float) $item->humidity,
                    'precipitation_rate' => (float) $item->precipitation_rate,
                    'precipitation_total' => (float) $item->precipitation_total,
                    'date_time' => $item->date_time,
                ]));
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
