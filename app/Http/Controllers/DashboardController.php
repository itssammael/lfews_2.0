<?php

namespace App\Http\Controllers;

use App\Services\ModbusService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // dd(\Illuminate\Support\Facades\Cache::get('modbus_history'));
        return Inertia::render('Dashboard', [
            'sensors' => \App\Models\WaterLevelSensor::all(),
            'latestData' => \Illuminate\Support\Facades\Cache::get('latest_modbus_data'),
            'historyData' => \Illuminate\Support\Facades\Cache::get('modbus_history', [])
        ]);
    }

    public function pullWaterData(ModbusService $modbusService)
    {
        // Simple lock to prevent concurrent Modbus pulls
        $lockKey = 'modbus_pull_lock';
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            return redirect()->back()->with('warning', 'A data pull is already in progress.');
        }

        try {
            \Illuminate\Support\Facades\Cache::put($lockKey, true, 30); // 30 second lock

            $sensors = \App\Models\WaterLevelSensor::all();
            $results = [];

            foreach ($sensors as $sensor) {
                try {
                    $data = $modbusService->readModbusData(
                        $sensor->ip,
                        (int)$sensor->port,
                        1,
                        6,
                        (int)$sensor->slave_id,
                        1.5
                    );

                    $results[$sensor->id] = [
                        'sensor_id' => $sensor->id,
                        'name' => $sensor->name,
                        'success' => true,
                        'data' => $data[5],
                        'timestamp' => now()->toDateTimeString(),
                    ];
                } catch (\Exception $e) {
                    $results[$sensor->id] = [
                        'sensor_id' => $sensor->id,
                        'name' => $sensor->name,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toDateTimeString(),
                    ];
                }
            }

            // Update latest data
            \Illuminate\Support\Facades\Cache::put('latest_modbus_data', $results, 60);

            // Update history
            $history = \Illuminate\Support\Facades\Cache::get('modbus_history', []);
            foreach ($results as $sensorId => $result) {
                if ($result['success']) {
                    if (!isset($history[$sensorId])) {
                        $history[$sensorId] = [];
                    }
                    
                    $history[$sensorId][] = [
                        'value' => $result['data'] / 10,
                        'timestamp' => $result['timestamp']
                    ];

                    // Keep only last 50 points
                    if (count($history[$sensorId]) > 50) {
                        array_shift($history[$sensorId]);
                    }
                }
            }
            \Illuminate\Support\Facades\Cache::put('modbus_history', $history, 1440); // 24 hours

            \Illuminate\Support\Facades\Cache::forget($lockKey);

            return redirect()->route('dashboard')->with('modbusResult', $results);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::forget($lockKey);
            $errorResult = [
                'system' => [
                    'sensor_id' => 0,
                    'name' => 'System Error',
                    'success' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toDateTimeString(),
                ]
            ];
            return redirect()->route('dashboard')->with('modbusResult', $errorResult);
        }
    }
}
