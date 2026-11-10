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
            try {
                $timeoutSettings = SystemSetting::where('name', 'data_pull_timeout')->first()?->value;
                $timeout = max(5, (int) ($timeoutSettings['weather_station'] ?? 300));

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
                            $obsDateTime = Carbon::parse($obs['obsTimeLocal'])->toDateTimeString();
                            $trimmedData = [
                                'temperature' => $obs['metric']['temp'] ?? null,
                                'heat_index' => $obs['metric']['heatIndex'] ?? null,
                                'dewpoint' => $obs['metric']['dewpt'] ?? null,
                                'humidity' => $obs['humidity'] ?? null,
                                'wind_speed' => $obs['metric']['windSpeed'] ?? null,
                                'wind_direction' => $obs['winddir'] ?? null,
                                'wind_gust' => $obs['metric']['windGust'] ?? null,
                                'pressure' => $obs['metric']['pressure'] ?? null,
                                'precipitation_rate' => $obs['metric']['precipRate'] ?? null,
                                'precipitation_total' => $obs['metric']['precipTotal'] ?? null,
                                'uv' => $obs['uv'] ?? null,
                                'solar_radiation' => $obs['solarRadiation'] ?? null,
                                'date_time' => $obsDateTime,
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

                            WeatherStationObservationData::updateOrCreate(
                                [
                                    'weather_station_id' => $station->id,
                                    'date_time' => $obsDateTime,
                                ],
                                $trimmedData
                            );
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
                    } catch (\Throwable $e) {
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

                // Update latest data (24 hours TTL)
                \Illuminate\Support\Facades\Cache::put('latest_weather_observation_data', $observations, 86400);

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
                \Illuminate\Support\Facades\Cache::put('weather_observation_history', $history, 86400);

                $this->info('[' . now()->toDateTimeString() . '] Processed ' . count($observations) . ' stations.');
                sleep($timeout);
            } catch (\Throwable $e) {
                $this->error('[' . now()->toDateTimeString() . '] Loop error: ' . $e->getMessage());
                sleep(10);
            }
        }
    }
}
