<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

use App\Models\WeatherStationObservationData;

class PullWundergroundApiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pull-wunderground-api-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls data from Wunderground API every 60 seconds and stores it in cache.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Wunderground API data pulling loop for all stations...');

        while (true) {
            $stations = \App\Models\WeatherStation::where('mode', 'API/wunderground')->get();
            $observations = [];
            
            foreach ($stations as $station) {
                $this->info("Pulling data for station: {$station->name} ({$station->station_id})");
                try {
                    $response = Http::get('https://api.weather.com/v2/pws/observations/current', [
                        'stationId' => $station->station_id,
                        'format' => 'json',
                        'units' => 'm',
                        'numericPrecision' => 'decimal',
                        'apiKey' => 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d',
                    ]);
                    
                    if ($response->successful()) {
                        $weatherData = $response->json();
                        
                        if (isset($weatherData['observations']) && !empty($weatherData['observations'])) {
                            $obs = $weatherData['observations'][0];
                            $trimmedData = [
                                'temperature' => $obs['metric']['temp'],
                                'heat_index' => $obs['metric']['heatIndex'],
                                'dewpoint' => $obs['metric']['dewpt'],
                                'humidity' => $obs['humidity'],
                                'wind_speed' => $obs['metric']['windSpeed'],
                                'wind_direction' => $obs['winddir'],
                                'wind_gust' => $obs['metric']['windGust'],
                                'pressure' => $obs['metric']['pressure'],
                                'precipitation_rate' => $obs['metric']['precipRate'],
                                'precipitation_total' => $obs['metric']['precipTotal'],
                                'uv' => $obs['uv'],
                                'solar_radiation' => $obs['solarRadiation'],
                                'date_time' => Carbon::parse($obs['obsTimeLocal'])->toDateTimeString(),
                                'weather_station_id' => $station->id,
                            ];
                            
                            $observations[$station->id] = [
                                'id' => $station->id,
                                'station_id' => $station->station_id,
                                'name' => $station->name,
                                'success' => true,
                                'data' => $trimmedData,
                                'timestamp' => now()->toDateTimeString(),
                            ];
                             
                            WeatherStationObservationData::create($trimmedData);
                            $this->info("Successfully pulled and stored data for {$station->name}");
                        } else {
                            $this->warn("No observations found for station: {$station->name}");
                        }
                    } else {
                        $this->error("API call failed for station {$station->name}: " . $response->status());
                    }
                } catch (\Exception $e) {
                    $this->error("Error for station {$station->name}: " . $e->getMessage());
                    $observations[$station->id] = [
                        'id' => $station->id,
                        'station_id' => $station->station_id,
                        'name' => $station->name,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toDateTimeString(),
                    ];
                }
            }

            // Update latest data
            \Illuminate\Support\Facades\Cache::put('latest_wunderground_api_data', $observations, 60);
            
            // Update history
            $history = \Illuminate\Support\Facades\Cache::get('wunderground_api_history', []);
            foreach ($observations as $stationId => $observation) {
                if ($observation['success']) {
                    if (!isset($history[$stationId])) {
                        $history[$stationId] = [];
                    }
                    
                    $history[$stationId][] = [
                        'data' => $observation['data'],
                        'timestamp' => $observation['timestamp']
                    ];

                    if (count($history[$stationId]) > 50) {
                        array_shift($history[$stationId]);
                    }
                }
            }
            \Illuminate\Support\Facades\Cache::put('wunderground_api_history', $history, 1440); 
            
            $this->info('[' . now()->toDateTimeString() . '] Processed ' . count($observations) . ' stations.');
            sleep(60);
        }
    }
}
