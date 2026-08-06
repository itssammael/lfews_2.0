<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaterLevelSensorData;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;

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
    public function handle(\App\Services\ModbusService $modbusService)
    {
        $this->info('Starting Modbus data pulling loop for all sensors...');

        while (true) {
            $timeoutSettings = SystemSetting::where('name', 'data_pull_timeout')->first()?->value;
            $timeout = (float) ($timeoutSettings['water_level_sensor'] ?? 300);

            $sensors = \App\Models\WaterLevelSensor::where('state', 1)->get();
            $results = [];

            foreach ($sensors as $sensor) {
                try {
                    if ($sensor->mode === 'ModBus') {
                        $data = $modbusService->readModbusData(
                            $sensor->ip,
                            (int) $sensor->port,
                            1, // startAddress (from previous requirement)
                            6, // quantity
                            (int) $sensor->slave_id,
                            3.0// timeout
                        );

                        if ($data[5] !== 0) {
                            $results[$sensor->id] = [
                                'sensor_id' => $sensor->id,
                                'name' => $sensor->name,
                                'success' => true,
                                'data' => $data[5] / 10,
                                'timestamp' => now()->toDateTimeString(),
                            ];

                            WaterLevelSensorData::create([
                                'water_level_sensor_id' => $sensor->id,
                                'sensor_data' => $data[5] / 10,
                                'date' => now()->toDateTimeString(),
                            ]);
                        }
                    } else {
                        $waterLevel = $this->pullBynWLSSensor($sensor);
                        $results[$sensor->id] = [
                            'sensor_id' => $sensor->id,
                            'name' => $sensor->name,
                            'success' => true,
                            'data' => $waterLevel,
                            'timestamp' => now()->toDateTimeString(),
                        ];
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

            \Illuminate\Support\Facades\Cache::put('latest_modbus_data', $results, 60);

            // Update history
            $history = \Illuminate\Support\Facades\Cache::get('modbus_history', []);
            if (empty($history)) {
                $todayData = WaterLevelSensorData::whereDate('date', Carbon::today())
                    ->orderBy('date', 'asc')
                    ->limit(50)
                    ->get();

                foreach ($todayData as $entry) {
                    if (!isset($history[$entry->water_level_sensor_id])) {
                        $history[$entry->water_level_sensor_id] = [];
                    }
                    $history[$entry->water_level_sensor_id][] = [
                        'value' => $entry->sensor_data,
                        'timestamp' => $entry->date,
                    ];

                }
            }
            foreach ($results as $sensorId => $result) {

                if ($result['success']) {
                    if (!isset($history[$sensorId])) {
                        $history[$sensorId] = [];
                    }

                    $history[$sensorId][] = [
                        'value' => $result['data'],
                        'timestamp' => $result['timestamp']
                    ];


                    // Keep only last 50 points
                    if (count($history[$sensorId]) > 50) {
                        array_shift($history[$sensorId]);
                    }
                }
            }
            \Illuminate\Support\Facades\Cache::put('modbus_history', $history, 60); // 24 hours

            $this->info('[' . now()->toDateTimeString() . '] Pulled data for ' . $sensors->count() . ' sensors.');

            sleep((int) $timeout);
        }
    }

    private function pullBynWLSSensor($sensor)
    {
        $mode = is_array($sensor) ? ($sensor['mode'] ?? '') : ($sensor->mode ?? '');
        $ip = is_array($sensor) ? ($sensor['ip'] ?? '') : ($sensor->ip ?? '');
        $sensorId = is_array($sensor) ? ($sensor['id'] ?? null) : ($sensor->id ?? null);

        $sensormode = explode("/", $mode);
        $apikey = $sensormode[1] ?? '';

        if (!preg_match("~^(?:f|ht)tps?://~i", $ip)) {
            $ip = "http://" . $ip;
        }

        $url = $ip . "/api/waterlevel?api_key=" . $apikey;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "X-MB-API-KEY: " . $apikey,
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("cURL Error #" . $err);
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new \Exception("Invalid JSON response from sensor API: " . $response);
        }

        $waterLevel = isset($data['water_level']) ? (float) $data['water_level'] : 0;

        if ($sensorId) {
            WaterLevelSensorData::create([
                'water_level_sensor_id' => $sensorId,
                'sensor_data' => $waterLevel,
                'date' => now()->toDateTimeString(),
            ]);
        }

        return $waterLevel;
    }
}
