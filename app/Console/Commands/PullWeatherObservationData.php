<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use App\Models\SystemSetting;


use App\Models\WeatherStationObservationData;

class PullWeatherObservationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pull-weather-observation-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls weather observation data for all active stations and stores it in cache.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weather observation data pulling loop for all stations...');
        $controller = new \App\Http\Controllers\WeatherStationController();
        while (true) {
            $timeoutSettings = SystemSetting::where('name', 'data_pull_timeout')->first()?->value;
            $timeout = (float) ($timeoutSettings['weather_station'] ?? 300);

            $stations = \App\Models\WeatherStation::where('state', 1)->get();
            $observations = [];

            foreach ($stations as $station) {
                $this->info("Pulling data for station: {$station->name} ({$station->station_id})");
                try {
                    $weatherData = null;
                    if ($station->mode === "API/wunderground") {
                        $response = $controller->fetchWundergroundData($station);
                        if ($response && $response->successful()) {
                            $weatherData = $response->json();
                        } else {
                            $this->error("API call failed for station {$station->name}: " . ($response ? $response->status() : 'No response'));
                        }
                    } elseif ($station->mode === "Davis") {
                        $weatherData = $controller->davisWeatherStation($station);
                        if (!$weatherData) {
                            $this->error("Failed to connect to Davis station at {$station->ip}");
                        }
                    }

                    if ($weatherData && isset($weatherData['observations']) && !empty($weatherData['observations'])) {
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
                        $this->warn("No observations found or data pull failed for station: {$station->name}");
                        $observations[$station->id] = [
                            'id' => $station->id,
                            'station_id' => $station->station_id,
                            'name' => $station->name,
                            'success' => false,
                            'error' => 'No observations found or data pull failed',
                            'timestamp' => now()->toDateTimeString(),
                        ];
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
            \Illuminate\Support\Facades\Cache::put('latest_weather_observation_data', $observations, 60);

            // Update history
            $history = \Illuminate\Support\Facades\Cache::get('weather_observation_history', []);
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
            \Illuminate\Support\Facades\Cache::put('weather_observation_history', $history, 60);

            $this->info('[' . now()->toDateTimeString() . '] Processed ' . count($observations) . ' stations.');
            sleep((int) $timeout);
        }
    }
}
