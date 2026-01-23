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
            'latestData' => \Illuminate\Support\Facades\Cache::get('latest_modbus_data')
        ]);
    }

    public function pullWaterData(ModbusService $modbusService)
    {
        try {
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
                        'data' => $data,
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

            \Illuminate\Support\Facades\Cache::put('latest_modbus_data', $results, 60);

            return redirect()->route('dashboard')->with('modbusResult', $results);
        } catch (\Exception $e) {
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
