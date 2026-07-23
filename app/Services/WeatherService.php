<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WeatherService
{
    protected string $apiKey;
    protected string $stationId;

    public function __construct(
        string $stationId = 'IBAYAW1',
        string $apiKey = 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d'
    ) {
        $this->stationId = $stationId;
        $this->apiKey = $apiKey ? $apiKey : 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d';
    }

    /**
     * Fetch observations for a specific date range.
     *
     * @param string $startDate 'YYYY-MM-DD'
     * @param string $endDate   'YYYY-MM-DD'
     * @return Collection
     */
    public function getPwsTableData(string $startDate, string $endDate): Collection
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $allRows = collect();

        // Loop through each date in the range
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $formattedDate = $date->format('Ymd');
            $isToday = $date->isToday();

            if ($isToday) {
                $endpoint = "http://api.weather.com/v2/pws/observations/all/1day";
                $queryParams = [
                    'apiKey' => $this->apiKey,
                    'stationId' => $this->stationId,
                    'numericPrecision' => 'decimal',
                    'format' => 'json',
                    'units' => 'm',
                ];
            } else {
                $endpoint = "http://api.weather.com/v2/pws/history/all";
                $queryParams = [
                    'apiKey' => $this->apiKey,
                    'stationId' => $this->stationId,
                    'numericPrecision' => 'decimal',
                    'format' => 'json',
                    'units' => 'm',
                    'date' => $formattedDate,
                ];
            }

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ])->get($endpoint, $queryParams);

            if ($response->failed()) {
                logger()->warning("Failed to fetch weather data for station {$this->stationId} on date {$date->toDateString()}: " . $response->status() . " - " . $response->body());
                continue;
            }

            $observations = $response->json('observations', []);

            $parsedObservations = collect($observations)->map(function ($obs) {
                $metric = $obs['metric'] ?? [];

                // Format display time
                $obsTime = $obs['obsTimeLocal'] ?? '';

                // $windDir = $obs['winddirAvg'] ?? $obs['winddir'] ?? null;
                // if (is_numeric($windDir)) {
                //     $cardinals = ["North", "NNE", "NE", "ENE", "East", "ESE", "SE", "SSE", "South", "SSW", "SW", "WSW", "West", "WNW", "NW", "NNW"];
                //     $index = (int) round(($windDir % 360) / 22.5);
                //     $windDir = $cardinals[$index % 16];
                // }

                return [
                    'date_time' => $obsTime,
                    'temperature' => $metric['tempAvg'] ?? $metric['temp'] ?? null,
                    'heat_index' => $metric['heatindexAvg'] ?? $metric['heatIndex'] ?? null,
                    'dewpoint' => $metric['dewptAvg'] ?? $metric['dewpt'] ?? null,
                    'humidity' => $obs['humidityAvg'] ?? $obs['humidity'] ?? null,
                    'wind_speed' => $metric['windspeedAvg'] ?? $metric['windSpeed'] ?? null,
                    'wind_direction' => $obs['winddirAvg'] ?? $obs['winddir'] ?? null,
                    'wind_gust' => $metric['windgustAvg'] ?? $metric['windGust'] ?? null,
                    'pressure' => $metric['pressureMax'] ?? $metric['pressure'] ?? null,
                    'precipitation_rate' => $metric['precipRate'] ?? null,
                    'precipitation_total' => $metric['precipTotal'] ?? null,
                    'uv' => $obs['uvHigh'] ?? $obs['uv'] ?? 0,
                    'solar_radiation' => $obs['solarRadiationHigh'] ?? $obs['solarRadiation'] ?? 0,
                ];
            });

            $allRows = $allRows->concat($parsedObservations);
        }

        return $allRows;
    }
}
