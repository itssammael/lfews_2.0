<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaterLevelSensorData;
use App\Models\WaterLevelSensor;
use App\Models\SystemSetting;
use App\Services\ModbusService;
use App\Http\Controllers\WaterLevelSensorController;

class PullModbusData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pull-modbus-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls data from Modbus device every 10 seconds and stores it in cache.';

    /**
     * Execute the console command.
     */
    public function handle(ModbusService $modbusService, WaterLevelSensorController $waterLevelSensorController)
    {
        $this->info('Starting Modbus data pulling loop for all sensors...');

        while (true) {
            $timeoutSettings = SystemSetting::where('name', 'data_pull_timeout')->first()?->value;
            $timeout = (float) ($timeoutSettings['water_level_sensor'] ?? 300);

            $sensors = WaterLevelSensor::where('state', 1)->get();
            $results = [];

            foreach ($sensors as $sensor) {
                try {
                    $result = $waterLevelSensorController->formatSensorData($sensor, $modbusService);
                    $results[$sensor->id] = $result;

                    if ($result['success']) {
                        $this->storeWaterLevelSensorData($sensor->id, $result['data'], $result['timestamp']);
                    }
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

            $waterLevelSensorController->updateModbusCache($results);

            $this->info('[' . now()->toDateTimeString() . '] Pulled data for ' . $sensors->count() . ' sensors.');

            sleep((int) $timeout);
        }
    }

    private function storeWaterLevelSensorData($sensorId, $sensorData, $date)
    {
        WaterLevelSensorData::create([
            'water_level_sensor_id' => $sensorId,
            'sensor_data' => $sensorData,
            'date' => $date,
        ]);
    }
}
