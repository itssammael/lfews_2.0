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
        \Illuminate\Support\Facades\Gate::authorize('can-read');
        $sensors = WaterLevelSensor::where('state', 1)->get();
        $stations = WeatherStation::where('state', 1)->get();

        $waterLevelYears = WaterLevelSensorData::selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $weatherStationYears = WeatherStationObservationData::selectRaw('YEAR(date_time) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $hiSettings = \App\Models\SystemSetting::where('name', 'heat_index_advisory_gauge')->first()?->value ?? [];

        return Inertia::render('LFEWS/Reports', [
            'sensors' => $sensors,
            'stations' => $stations,
            'waterLevelYears' => $waterLevelYears,
            'weatherStationYears' => $weatherStationYears,
            'hiSettings' => $hiSettings,
        ]);
    }

    public function getWaterLevelData(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');
        $query = WaterLevelSensorData::with('sensor');

        if ($request->has('sensor') && $request->sensor !== 'All' && $request->sensor !== '') {
            $query->whereHas('sensor', function ($q) use ($request) {
                $q->where('name', $request->sensor);
            });
        }

        if ($request->reportType === 'Monthly') {
            $query->whereYear('date', $request->year)
                ->whereMonth('date', date('m', strtotime('1 ' . $request->month)));
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
                $monthNum = date('m', strtotime('1 ' . $request->month));
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
        \Illuminate\Support\Facades\Gate::authorize('can-read');
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

        $monthsList = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $fromIdx = 0;
        $toIdx = 11;

        if ($request->reportType === 'Monthly') {
            $query->whereYear('date_time', $request->year)
                ->whereMonth('date_time', date('m', strtotime('1 ' . $request->month)));
        } else if ($request->reportType === 'Month Range') {
            $monthFrom = $request->monthFrom ?: 'January';
            $monthTo = $request->monthTo ?: 'December';
            $fromIdx = array_search($monthFrom, $monthsList);
            $toIdx = array_search($monthTo, $monthsList);
            if ($fromIdx === false) $fromIdx = 0;
            if ($toIdx === false) $toIdx = 11;
            if ($fromIdx > $toIdx) {
                $temp = $fromIdx;
                $fromIdx = $toIdx;
                $toIdx = $temp;
            }
            $startMonthNum = sprintf('%02d', $fromIdx + 1);
            $endMonthNum = sprintf('%02d', $toIdx + 1);
            $startDate = "{$request->year}-{$startMonthNum}-01 00:00:00";
            $endDate = date('Y-m-t 23:59:59', strtotime("{$request->year}-{$endMonthNum}-01"));
            $query->whereBetween('date_time', [$startDate, $endDate]);
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
            $monthNum = date('m', strtotime('1 ' . $request->month));
            $startDate = "{$request->year}-{$monthNum}-01 00:00:00";
            $endDate = date('Y-m-t 23:59:59', strtotime($startDate));
        } else if ($request->reportType === 'Month Range') {
            $startMonthNum = sprintf('%02d', $fromIdx + 1);
            $endMonthNum = sprintf('%02d', $toIdx + 1);
            $startDate = "{$request->year}-{$startMonthNum}-01 00:00:00";
            $endDate = date('Y-m-t 23:59:59', strtotime("{$request->year}-{$endMonthNum}-01"));
        } else {
            $startDate = $request->from . ' 00:00:00';
            $endDate = $request->to . ' 23:59:59';
        }

        // Get summary and chart records for ALL stations or a specific station
        $aggQuery = DB::query()
            ->select(
                'weather_stations.name as station_name',
                'date_only',
                'temperature',
                'humidity',
                'dewpoint',
                'heat_index',
                'precipitation_rate',
                'precipitation_total',
                'wind_speed',
                'wind_direction',
                'wind_gust'
            )
            ->fromSub(function ($subQuery) use ($startDate, $endDate, $request, $isAllStations) {
                $subQuery->select(
                    'weather_station_id',
                    DB::raw('DATE(date_time) as date_only'),
                    'temperature',
                    'humidity',
                    'dewpoint',
                    'heat_index',
                    'precipitation_rate',
                    'precipitation_total',
                    'wind_speed',
                    'wind_direction',
                    'wind_gust',
                    'date_time',
                    DB::raw('ROW_NUMBER() OVER (PARTITION BY weather_station_id, DATE(date_time) ORDER BY ' .
                        ($request->report === 'Heat Index' ? 'heat_index DESC, date_time DESC' :
                            ($request->report === 'Wind Speed' ? 'wind_speed DESC, date_time DESC' : 'date_time DESC')) . ') as `rank`')
                )
                    ->from('weather_station_observation_data')
                    ->whereBetween('date_time', [$startDate, $endDate])
                    ->whereTime('date_time', '<=', '23:59:00');

                if (!$isAllStations) {
                    $subQuery->whereIn('weather_station_id', function ($q) use ($request) {
                        $q->select('id')->from('weather_stations')->where('name', $request->station);
                    });
                }
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
                'wind_speed' => (float) $record->wind_speed,
                'wind_direction' => (float) $record->wind_direction,
                'wind_gust' => (float) $record->wind_gust,
                'date_time' => $record->date_only . ' 23:59:00',
            ];
        });

        $chartDataAvg = [];
        $chartDataMax = [];
        $monthlyHeatIndexRecords = [];

        if ($request->report === 'Heat Index' && $request->reportType === 'Month Range') {
            $selectedMonths = array_slice($monthsList, $fromIdx, $toIdx - $fromIdx + 1);

            $stationsQuery = WeatherStation::query();
            if (!$isAllStations) {
                $stationsQuery->where('name', $request->station);
            } else {
                $stationsQuery->whereHas('observations', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_time', [$startDate, $endDate]);
                });
            }
            $activeStations = $stationsQuery->pluck('name')->toArray();
            if (empty($activeStations) && !$isAllStations) {
                $activeStations = [$request->station];
            }
            $stationNames = $activeStations;

            $monthlyAggQuery = DB::table('weather_station_observation_data')
                ->join('weather_stations', 'weather_station_observation_data.weather_station_id', '=', 'weather_stations.id')
                ->whereBetween('weather_station_observation_data.date_time', [$startDate, $endDate]);

            if (!$isAllStations) {
                $monthlyAggQuery->where('weather_stations.name', $request->station);
            }

            $monthlyAggData = $monthlyAggQuery
                ->select(
                    'weather_stations.name as station_name',
                    DB::raw('MONTH(date_time) as month_num'),
                    DB::raw('ROUND(AVG(heat_index), 2) as avg_heat_index'),
                    DB::raw('ROUND(MAX(heat_index), 2) as max_heat_index')
                )
                ->groupBy('weather_stations.name', DB::raw('MONTH(date_time)'))
                ->get();

            $lookup = [];
            foreach ($monthlyAggData as $row) {
                $mNum = (int) $row->month_num;
                $sName = $row->station_name;
                $lookup[$mNum][$sName] = [
                    'avg' => (float) $row->avg_heat_index,
                    'max' => (float) $row->max_heat_index
                ];
            }

            foreach ($selectedMonths as $mName) {
                $mNum = array_search($mName, $monthsList) + 1;
                $avgRow = ['month' => $mName];
                $maxRow = ['month' => $mName];

                foreach ($stationNames as $sName) {
                    $val = $lookup[$mNum][$sName] ?? null;
                    $avgVal = $val ? $val['avg'] : null;
                    $maxVal = $val ? $val['max'] : null;

                    $avgRow[$sName] = $avgVal;
                    $maxRow[$sName] = $maxVal;

                    $monthlyHeatIndexRecords[] = [
                        'month' => $mName,
                        'station_name' => $sName,
                        'avg_heat_index' => $avgVal !== null ? $avgVal : '-',
                        'max_heat_index' => $maxVal !== null ? $maxVal : '-'
                    ];
                }

                $chartDataAvg[] = $avgRow;
                $chartDataMax[] = $maxRow;
            }
        } else if ($request->report === 'Rain') {
            $chartData = $summaryResults->groupBy('date_only')->map(function ($items, $date) {
                $entry = ['date' => $date];
                foreach ($items as $item) {
                    $entry[$item->station_name] = (float) $item->precipitation_total;
                }
                return $entry;
            })->values()->toArray();

            if ($isAllStations) {
                $stationNames = WeatherStation::whereHas('observations', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_time', [$startDate, $endDate]);
                })->pluck('name')->toArray();
            } else {
                $stationNames = [$request->station];
            }
        } else if ($request->report === 'Wind Speed') {
            $chartData = $summaryResults->groupBy('date_only')->map(function ($items, $date) {
                $entry = ['date' => $date];
                foreach ($items as $item) {
                    $entry[$item->station_name] = (float) $item->wind_speed;
                }
                return $entry;
            })->values()->toArray();

            if ($isAllStations) {
                $stationNames = WeatherStation::whereHas('observations', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_time', [$startDate, $endDate]);
                })->pluck('name')->toArray();
            } else {
                $stationNames = [$request->station];
            }
        } else if (str_contains($request->report, 'Wind Direction')) {
            if ($isAllStations) {
                $stationNames = WeatherStation::whereHas('observations', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_time', [$startDate, $endDate]);
                })->pluck('name')->toArray();
            } else {
                $stationNames = [$request->station];
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
                    'wind_speed' => (float) $record->wind_speed,
                    'wind_direction' => (float) $record->wind_direction,
                    'wind_gust' => (float) $record->wind_gust,
                    'date_time' => $record->date_time,
                ];
            }),
            'chartData' => $chartData,
            'chartDataAvg' => $chartDataAvg,
            'chartDataMax' => $chartDataMax,
            'monthlyHeatIndexRecords' => $monthlyHeatIndexRecords,
            'stationNames' => $stationNames,
            'summaryRecords' => $summaryRecords
        ]);
    }
}
