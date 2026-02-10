<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\WaterLevelSensor;
use App\Models\WaterLevelSensorData;
use App\Models\WeatherStation;
use App\Models\WeatherStationObservationData;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $sensors = WaterLevelSensor::all();
        $stations = WeatherStation::all();

        $waterLevelYears = WaterLevelSensorData::selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $weatherStationYears = WeatherStationObservationData::selectRaw('YEAR(date_time) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('Reports', [
            'sensors' => $sensors,
            'stations' => $stations,
            'waterLevelYears' => $waterLevelYears,
            'weatherStationYears' => $weatherStationYears,

        ]);
    }

    public function getWaterLevelData(Request $request)
    {
        $query = WaterLevelSensorData::with('sensor');

        if ($request->has('sensor') && $request->sensor !== 'All' && $request->sensor !== '') {
            $query->whereHas('sensor', function ($q) use ($request) {
                $q->where('name', $request->sensor);
            });
        }

        if ($request->reportType === 'Monthly') {
            $query->whereYear('date', $request->year)
                ->whereMonth('date', date('m', strtotime($request->month)));
        } else {
            if ($request->from && $request->to) {
                $query->whereBetween('date', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            }
        }

        $records = $query->orderBy('date', 'asc')->get();

        $summaryRecords = [];
        if ($request->has('sensor') && $request->sensor === 'All') {
            $isAllSensors = true;
            $startDate = '';
            $endDate = '';

            if ($request->reportType === 'Monthly') {
                $monthNum = date('m', strtotime($request->month));
                $startDate = "{$request->year}-{$monthNum}-01 00:00:00";
                $endDate = date('Y-m-t 23:59:59', strtotime($startDate));
            } else {
                $startDate = $request->from . ' 00:00:00';
                $endDate = $request->to . ' 23:59:59';
            }

            $summaryQuery = DB::query()
                ->select('water_level_sensors.name as sensor_name', 'date_only as date_time', 'sensor_data as water_level')
                ->fromSub(function ($query) use ($startDate, $endDate) {
                    $query->select(
                        'water_level_sensor_id',
                        DB::raw('DATE(date) as date_only'),
                        'sensor_data',
                        'date',
                        DB::raw('ROW_NUMBER() OVER (PARTITION BY water_level_sensor_id, DATE(date) ORDER BY date DESC) as `rank`')
                    )
                        ->from('water_level_sensor_data')
                        ->whereBetween('date', [$startDate, $endDate])
                        ->whereTime('date', '<=', '23:59:00');
                }, 'ranked_data')
                ->join('water_level_sensors', 'ranked_data.water_level_sensor_id', '=', 'water_level_sensors.id')
                ->where('rank', 1)
                ->orderBy('date_only', 'asc');

            $summaryRecords = $summaryQuery->get()->map(function ($record) {
                return [
                    'sensor_name' => $record->sensor_name,
                    'water_level' => (float) $record->water_level,
                    'date_time' => $record->date_time . ' 23:59:00', // Label it as end of day
                ];
            });
        }

        $thresholds = null;
        // ... (existing thresholds logic)
        if ($request->has('sensor') && $request->sensor !== 'All' && $request->sensor !== '') {
            $sensor = WaterLevelSensor::where('name', $request->sensor)->first();
            if ($sensor) {
                $thresholds = [
                    'level_2' => $sensor->level_2,
                    'level_3' => $sensor->level_3,
                    'level_4' => $sensor->level_4,
                    'name' => $sensor->name
                ];
            }
        }

        return response()->json([
            'records' => $records->map(function ($record) {
                return [
                    'sensor_name' => $record->sensor->name,
                    'water_level' => (float) $record->sensor_data,
                    'date_time' => $record->date,
                ];
            }),
            'summaryRecords' => $summaryRecords,
            'thresholds' => $thresholds
        ]);
    }

    public function getWeatherObservationData(Request $request)
    {
        $query = WeatherStationObservationData::with('weatherStation');

        if ($request->has('station') && $request->station !== 'All' && $request->station !== '') {
            $query->whereHas('weatherStation', function ($q) use ($request) {
                $q->where('name', $request->station);
            });
        }

        if ($request->report === 'Rain') {
            $query->where(function ($q) {
                $q->where('precipitation_rate', '>', 0)
                    ->orWhere('precipitation_total', '>', 0);
            });
        }

        if ($request->reportType === 'Monthly') {
            $query->whereYear('date_time', $request->year)
                ->whereMonth('date_time', date('m', strtotime($request->month)));
        } else {
            if ($request->from && $request->to) {
                $query->whereBetween('date_time', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            }
        }

        $records = $query->orderBy('date_time', 'asc')->get();

        $chartData = [];
        $stationNames = [];
        $summaryRecords = [];

        $isAllStations = !($request->station && $request->station !== 'All' && $request->station !== '');

        $startDate = '';
        $endDate = '';
        if ($request->reportType === 'Monthly') {
            $monthNum = date('m', strtotime($request->month));
            $startDate = "{$request->year}-{$monthNum}-01 00:00:00";
            $endDate = date('Y-m-t 23:59:59', strtotime($startDate));
        } else {
            $startDate = $request->from . ' 00:00:00';
            $endDate = $request->to . ' 23:59:59';
        }

        if ($isAllStations) {
            $stationId = null;
            // Get summary records for ALL report types when station is All
            $aggQuery = DB::query()
                ->select(
                    'weather_stations.name as station_name',
                    'date_only',
                    'temperature',
                    'humidity',
                    'dewpoint',
                    'heat_index',
                    'precipitation_rate',
                    'precipitation_total'
                )
                ->fromSub(function ($query) use ($startDate, $endDate) {
                    $query->select(
                        'weather_station_id',
                        DB::raw('DATE(date_time) as date_only'),
                        'temperature',
                        'humidity',
                        'dewpoint',
                        'heat_index',
                        'precipitation_rate',
                        'precipitation_total',
                        'date_time',
                        DB::raw('ROW_NUMBER() OVER (PARTITION BY weather_station_id, DATE(date_time) ORDER BY date_time DESC) as `rank`')
                    )
                        ->from('weather_station_observation_data')
                        ->whereBetween('date_time', [$startDate, $endDate])
                        ->whereTime('date_time', '<=', '23:59:00');
                }, 'ranked_data')
                ->join('weather_stations', 'ranked_data.weather_station_id', '=', 'weather_stations.id')
                ->where('rank', 1)
                ->orderBy('date_only', 'asc');

            $summaryResults = $aggQuery->get();
            $summaryRecords = $summaryResults->map(function ($record) {
                return [
                    'station_name' => $record->station_name,
                    'temperature' => (float) $record->temperature,
                    'humidity' => (float) $record->humidity,
                    'dewpoint' => (float) $record->dewpoint,
                    'heat_index' => (float) $record->heat_index,
                    'precipitation_rate' => (float) $record->precipitation_rate,
                    'precipitation_total' => (float) $record->precipitation_total,
                    'date_time' => $record->date_only . ' 23:59:00',
                ];
            });

            if ($request->report === 'Rain') {
                $chartData = $summaryResults->groupBy('date_only')->map(function ($items, $date) {
                    $entry = ['date' => date('M d', strtotime($date))];
                    foreach ($items as $item) {
                        $entry[$item->station_name] = (float) $item->precipitation_total;
                    }
                    return $entry;
                })->values()->toArray();

                $stationNames = WeatherStation::whereHas('observations', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_time', [$startDate, $endDate]);
                })->pluck('name')->toArray();
            }
        } else {
            if ($request->report === 'Rain') {
                $station = WeatherStation::where('name', $request->station)->first();
                $stationNames = [$request->station];

                // For direct station, we still use the ranking logic for chart if needed, 
                // but usually the charts for single station use the raw records.
                // However, the existing implementation uses aggregated data for clustered series.
            }
        }

        return response()->json([
            'records' => $records->map(function ($record) {
                return [
                    'station_name' => $record->weatherStation->name,
                    'temperature' => (float) $record->temperature,
                    'humidity' => (float) $record->humidity,
                    'dewpoint' => (float) $record->dewpoint,
                    'heat_index' => (float) $record->heat_index,
                    'precipitation_rate' => (float) $record->precipitation_rate,
                    'precipitation_total' => (float) $record->precipitation_total,
                    'date_time' => $record->date_time,
                ];
            }),
            'chartData' => $chartData,
            'stationNames' => $stationNames,
            'summaryRecords' => $summaryRecords
        ]);
    }
}
