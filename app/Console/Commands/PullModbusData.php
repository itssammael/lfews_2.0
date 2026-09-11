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
            if (!$this->ensureDatabaseConnection()) {
                $this->warn('[' . now()->toDateTimeString() . '] Database connection unavailable. Retrying in 10 seconds...');
                sleep(10);
                continue;
            }

            try {
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
            } catch (QueryException | PDOException $e) {
                $this->catchDatabaseConnectionError($e);
                $this->warn('[' . now()->toDateTimeString() . '] Database error encountered during data pull. Retrying in 10 seconds...');
                sleep(10);
            } catch (\Exception $e) {
                $this->error('[' . now()->toDateTimeString() . '] Unexpected error: ' . $e->getMessage());
                sleep(10);
            }
        }
    }

    /**
     * Check and ensure that the database connection is active.
     * Attempts reconnection if the connection is lost.
     *
     * @return bool
     */
    private function ensureDatabaseConnection(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            return $this->catchDatabaseConnectionError($e);
        }
    }

    /**
     * Catch database connection errors and handle reconnection in case of network failure or disconnection.
     *
     * @param \Throwable $e
     * @return bool Returns true if successfully reconnected, false otherwise.
     */
    private function catchDatabaseConnectionError(\Throwable $e): bool
    {
        $this->error('[' . now()->toDateTimeString() . '] Database connection error: ' . $e->getMessage());

        try {
            $this->warn('[' . now()->toDateTimeString() . '] Attempting to purge and reconnect to the database...');
            DB::purge();
            DB::reconnect();
            DB::select('SELECT 1');
            $this->info('[' . now()->toDateTimeString() . '] Database connection re-established successfully.');
            return true;
        } catch (\Throwable $reconnectException) {
            $this->error('[' . now()->toDateTimeString() . '] Database reconnection failed: ' . $reconnectException->getMessage());
            return false;
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
