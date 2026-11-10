<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use PDOException;
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
            try {
                $timeoutSettings = SystemSetting::where('name', 'data_pull_timeout')->first()?->value;
                $timeout = max(5, (int) ($timeoutSettings['water_level_sensor'] ?? 300));

                $sensors = WaterLevelSensor::where('state', 1)->get();
                $results = [];

                foreach ($sensors as $sensor) {
                    try {
                        $result = $waterLevelSensorController->formatSensorData($sensor, $modbusService);
                        $results[$sensor->id] = $result;

                        if ($result['success']) {
                            $this->storeWaterLevelSensorData($sensor->id, $result['data'], $result['timestamp']);
                        }
                    } catch (\Throwable $e) {
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

                sleep($timeout);
            } catch (\Throwable $e) {
                $this->error('[' . now()->toDateTimeString() . '] Loop error: ' . $e->getMessage());
                sleep(10);
            }
        }
    }

    private function storeWaterLevelSensorData($sensorId, $sensorData, $date)
    {
        try {
            WaterLevelSensorData::create([
                'water_level_sensor_id' => $sensorId,
                'sensor_data' => $sensorData,
                'date' => $date,
            ]);
        } catch (QueryException | PDOException $e) {
            $reconnected = $this->catchDatabaseConnectionError($e);

            if ($reconnected) {
                // Retry saving once if connection was successfully restored
                WaterLevelSensorData::create([
                    'water_level_sensor_id' => $sensorId,
                    'sensor_data' => $sensorData,
                    'date' => $date,
                ]);
            } else {
                throw $e;
            }
        }
    }
}
