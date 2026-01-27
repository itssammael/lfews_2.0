<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    protected $description = 'Pulls data from Modbus device every 1 second and stores it in cache.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\ModbusService $modbusService)
    {
        $this->info('Starting Modbus data pulling loop for all sensors...');

        while (true) {
            $sensors = \App\Models\WaterLevelSensor::all();
            $results = [];

            foreach ($sensors as $sensor) {
                try {
                    $data = $modbusService->readModbusData(
                        $sensor->ip,
                        (int)$sensor->port,
                        1, // startAddress (from previous requirement)
                        6, // quantity
                        (int)$sensor->slave_id,
                        3.0// timeout
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
            
            $this->info('[' . now()->toDateTimeString() . '] Pulled data for ' . $sensors->count() . ' sensors.');

            sleep(1);
        }
    }
}
