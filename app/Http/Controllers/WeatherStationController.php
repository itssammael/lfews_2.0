<?php

namespace App\Http\Controllers;

use App\Models\WeatherStation;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class WeatherStationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allStations = WeatherStation::all();
        $paginatedStations = WeatherStation::query()
            ->with(['location', 'location.locationType'])
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('station_id', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%");
                });
            })
            ->paginate(6)
            ->withQueryString();

        return Inertia::render('LFEWS/WeatherStations', [
            'stations' => $paginatedStations,
            'filters' => request()->only(['search']),
            'showCreateModal' => false,
            'showEditModal' => false,
            'activeCount' => $allStations->where('state', 1)->count(),
            'inactiveCount' => $allStations->where('state', 0)->count(),
            'maintenanceCount' => $allStations->where('state', 2)->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $allStations = WeatherStation::all();
        $paginatedStations = WeatherStation::query()
            ->with(['location', 'location.locationType'])
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('station_id', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%");
                });
            })
            ->paginate(6)
            ->withQueryString();
        $locations = Location::with('locationType')->get();

        return Inertia::render('LFEWS/WeatherStations', [
            'stations' => $paginatedStations,
            'filters' => request()->only(['search']),
            'locations' => $locations,
            'showCreateModal' => true,
            'showEditModal' => false,
            'activeCount' => $allStations->where('state', 1)->count(),
            'inactiveCount' => $allStations->where('state', 0)->count(),
            'maintenanceCount' => $allStations->where('state', 2)->count(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'station_id' => 'required|string|max:255',
            'mode' => 'required|string|max:255',
            'key' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:255',
            'state' => 'required|integer', // Changed to integer validation
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ]);

        try {
            $location = Location::create([
                'latitude' => $validated['lat'],
                'longitude' => $validated['long'],
                'location_type_id' => 3, //Do no change this id
            ]);
            WeatherStation::create([
                'name' => $validated['name'],
                'station_id' => $validated['station_id'],
                'mode' => $validated['mode'],
                'key' => $validated['key'] ?? null,
                'ip' => $validated['ip'] ?? null,
                'state' => $validated['state'],
                'location_id' => $location->id,
            ]);
            return redirect()->route('weather-stations')->with('success', 'Weather station created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create weather station: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(WeatherStation $weatherStation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-update');
        $station = WeatherStation::find($id);

        if (!$station) {
            return redirect()->back()->with('error', 'Weather station not found.');
        }
        $location = Location::find($station->location_id);
        $station->location = ['latitude' => $location->latitude, 'longitude' => $location->longitude];

        $allStations = WeatherStation::all();
        $paginatedStations = WeatherStation::query()
            ->with(['location', 'location.locationType'])
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('station_id', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%");
                });
            })
            ->paginate(6)
            ->withQueryString();
        $locations = Location::with('locationType')->get();

        return Inertia::render('LFEWS/WeatherStations', [
            'stations' => $paginatedStations,
            'filters' => request()->only(['search']),
            'locations' => $locations,
            'editingStation' => $station,
            'showCreateModal' => false,
            'activeCount' => $allStations->where('state', 1)->count(),
            'inactiveCount' => $allStations->where('state', 0)->count(),
            'maintenanceCount' => $allStations->where('state', 2)->count(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WeatherStation $weatherStation)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-update');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'station_id' => 'required|string|max:255',
            'mode' => 'required|string|max:255',
            'key' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:255',
            'state' => 'required|integer',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'location_id' => 'required|exists:locations,id',
        ]);
        // dd($validated);
        try {
            $location = Location::find($validated['location_id']);
            if ($location) {
                $location->update([
                    'latitude' => $validated['lat'],
                    'longitude' => $validated['long'],
                ]);
            }

            $weatherStation->update([
                'name' => $validated['name'],
                'station_id' => $validated['station_id'],
                'mode' => $validated['mode'],
                'key' => $validated['key'] ?? null,
                'ip' => $validated['ip'] ?? null,
                'state' => $validated['state'],

            ]);
            return redirect()->route('weather-stations')->with('success', 'Weather station updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update weather station: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WeatherStation $weatherStation)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-delete');
        try {
            $weatherStation->delete();
            return redirect()->route('weather-stations')->with('success', 'Weather station deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete weather station: ' . $e->getMessage());
        }
    }


    public function pullObservationData()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        // Simple lock to prevent concurrent weather api pulls
        $lockKey = 'weather_pull_lock';
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            $message = 'Data pull is already in progress.';
            if (request()->ajax() || request()->wantsJson()) {
                return ['error' => $message];
            }
            return redirect()->back()->with('warning', $message);
        }

        try {
            \Illuminate\Support\Facades\Cache::put($lockKey, true, 30); // 30 second lock

            // Release session lock to prevent blocking other requests
            if (session_id()) {
                session_write_close();
            }
            set_time_limit(60);

            // $stations = WeatherStation::all();
            $stations = WeatherStation::where('state', 1)->get();
            $observations = [];
            foreach ($stations as $station) {
                try {
                    if ($station->mode === 'API/wunderground') {
                        $response = $this->fetchWundergroundData($station);
                        $weatherData = $response->json();
                    } elseif ($station->mode === 'Davis') {
                        $response = $this->davisWeatherStation($station);
                        $weatherData = $response;
                    }


                    $observations[$station->id] = [
                        'id' => $station->id,
                        'station_id' => $station->station_id,
                        'name' => $station->name,
                        'success' => true,
                        'data' => [
                            'temperature' => $weatherData['observations'][0]['metric']['temp'],
                            'heat_index' => $weatherData['observations'][0]['metric']['heatIndex'],
                            'dewpoint' => $weatherData['observations'][0]['metric']['dewpt'],
                            'humidity' => $weatherData['observations'][0]['humidity'],
                            'wind_speed' => $weatherData['observations'][0]['metric']['windSpeed'],
                            'wind_direction' => $weatherData['observations'][0]['winddir'],
                            'wind_gust' => $weatherData['observations'][0]['metric']['windGust'],
                            'pressure' => $weatherData['observations'][0]['metric']['pressure'],
                            'precipitation_rate' => $weatherData['observations'][0]['metric']['precipRate'],
                            'precipitation_total' => $weatherData['observations'][0]['metric']['precipTotal'],
                            'uv' => $weatherData['observations'][0]['uv'],
                            'solar_radiation' => $weatherData['observations'][0]['solarRadiation'],
                            'date_time' => $weatherData['observations'][0]['obsTimeLocal'],
                        ],
                        'timestamp' => now()->toDateTimeString(),

                    ];
                } catch (\Exception $e) {
                    $observations[$station->id] = [
                        'id' => $station->id,
                        'station_id' => $station->station_id,
                        'name' => $station->name,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toDateTimeString(),
                    ];

                }

            } //END of for loop

            // Update latest data
            \Illuminate\Support\Facades\Cache::put('latest_weather_observation_data', $observations, 60);

            //Update history
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

                    // Keep only last 50 points
                    if (count($history[$stationId]) > 50) {
                        array_shift($history[$stationId]);
                    }
                }
            }
            \Illuminate\Support\Facades\Cache::put('weather_observation_history', $history, 60); // 24 hours

            \Illuminate\Support\Facades\Cache::forget($lockKey);

            return $observations;

        } catch (\Exception $e) {

            \Illuminate\Support\Facades\Cache::forget($lockKey);
            $errorResult = [
                'system' => [
                    'sensor_id' => 0,
                    'name' => 'System Error',
                    'success' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toDateTimeString(),
                ]
            ];
            return $errorResult;

        }
    }

    public function fetchWundergroundData($station)
    {
        $apiKeySetting = \App\Models\SystemSetting::where('name', 'api_key')->first();
        $apiKey = $apiKeySetting ? $apiKeySetting->value['key'] : 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d';

        return Http::timeout(30)
            ->connectTimeout(15)
            ->withoutVerifying()
            ->retry(3, 2000)
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
            ->retry(3, 2000)
            ->get('http://api.weather.com/v2/pws/observations/current', [
                'stationId' => $station->station_id,
                'format' => 'json',
                'units' => 'm',
                'numericPrecision' => 'decimal',
                'apiKey' => $apiKey,
            ]);
    }

    public function davisWeatherStation($station)
    {
        $ip = $station->ip;
        $port = 22222;
        $fp = fsockopen($ip, $port, $errno, $errstr, 2);

        if ($fp) {
            stream_set_timeout($fp, 2); // Set read timeout to 2 seconds

            // 1. Wake up the console
            fwrite($fp, "\n");
            usleep(500000); // Wait 0.5s
            $response = fread($fp, 1024);

            // 2. Request 1 LOOP packet
            fwrite($fp, "LOOP 1\n");
            usleep(500000);

            // 3. Read binary data (1 byte ACK + 99 bytes packet)
            // Davis can be slow; ensure we get all 100 bytes
            $binaryData = "";
            $startTime = microtime(true);
            while (strlen($binaryData) < 100 && (microtime(true) - $startTime) < 2) {
                $chunk = fread($fp, 100 - strlen($binaryData));
                if ($chunk === false || $chunk === "")
                    break;
                $binaryData .= $chunk;
            }

            // 4. Verify ACK (Decimal 6) and we have a full packet
            if (strlen($binaryData) === 100 && ord($binaryData[0]) === 6) {
                $packet = substr($binaryData, 1);

                // // Individual bytes/offsets based on Davis LOOP 1 Packet Spec
                $barometer = unpack("v", substr($packet, 7, 2))[1];
                $temp_out = unpack("v", substr($packet, 12, 2))[1];
                $wind_speed = unpack("Cwind_speed/x4/", $packet);
                $wind_direction = unpack("v", substr($packet, 16, 2))[1];
                $humidity = ord($packet[33]);
                $rain_day = unpack("v", substr($packet, 52, 2))[1] / 100; // Rain rate (clicks/hr)
                $rain_rate = unpack("v", substr($packet, 41, 2))[1];
                $rain_total = unpack("v", substr($packet, 50, 2))[1];
                $wind_gust = unpack(
                    "x64/" .       // Skip to Offset 64
                    "vwind_gust",  // Read 2 bytes (unsigned short, little-endian)
                    $packet
                );
                $solar = unpack("v", substr($packet, 67, 2))[1];
                $uv = ord($packet[70]);
                $dewpoint = unpack("c", $packet[42])[1]; // 1-byte signed
                $heat_index = unpack("c", $packet[44])[1]; // 1-byte signed
                //    $rain_storm     = unpack("v", substr($packet, 46, 2))[1] / 100; // Offset 46
                // Scaling and "No Data" handling
                // Davis sentinel values: 32767 or 65535 for words, 255 for bytes

                $weather = [
                    'temperature' => ($temp_out == 32767) ? 0 : $temp_out / 10,
                    'dewpoint' => ($dewpoint == 255) ? 0 : $dewpoint,
                    'heat_index' => ($heat_index == 255) ? 0 : $heat_index,
                    'humidity' => ($humidity == 255) ? 0 : $humidity,
                    'wind_speed' => ($wind_speed['wind_speed'] == 255) ? 0 : $wind_speed['wind_speed'],
                    'wind_direction' => ($wind_direction == 32767 || $wind_direction == 0) ? 0 : $wind_direction,
                    'wind_gust' => ($wind_gust['wind_gust'] == 32767 || $wind_gust['wind_gust'] == 65535) ? 0 : $wind_gust['wind_gust'],
                    'pressure' => ($barometer == 0) ? 0 : $barometer / 10,
                    'precipitation_rate' => ($rain_rate == 65535) ? 0 : $rain_rate * 0.01,
                    'precipitation_total' => ($rain_total == 65535) ? 0 : $rain_total * 0.01,
                    'solar_radiation' => ($solar == 65535) ? 0 : $solar,
                    'uv' => ($uv == 255) ? 0 : $uv / 10,
                ];

                //    dd($rain_day);    
            }
            $weather['heat_index'] = $this->calculateHeatIndex($weather['temperature'], $weather['humidity']);
            $weather['dewpoint'] = $this->calculateDewPoint($weather['temperature'], $weather['humidity']);



            fclose($fp);
            $result = $this->formatResult($weather);
            return $result;
        }

    }

    public function formatResult($weather)
    {
        $weatherData['observations'][0]['humidity'] = $weather['humidity'];
        $weatherData['observations'][0]['winddir'] = $weather['wind_direction'];
        $weatherData['observations'][0]['uv'] = $weather['uv'];
        $weatherData['observations'][0]['solarRadiation'] = $weather['solar_radiation'];
        $weatherData['observations'][0]['metric']['temp'] = $this->convertFahrenheitToCelsius($weather['temperature']);
        $weatherData['observations'][0]['metric']['heatIndex'] = $this->convertFahrenheitToCelsius($weather['heat_index']);
        $weatherData['observations'][0]['metric']['dewpt'] = $this->convertFahrenheitToCelsius($weather['dewpoint']);
        $weatherData['observations'][0]['metric']['windGust'] = $weather['wind_gust'];
        $weatherData['observations'][0]['metric']['windSpeed'] = $this->convertMphToKph($weather['wind_speed']);
        $weatherData['observations'][0]['metric']['pressure'] = $weather['pressure'];
        $weatherData['observations'][0]['metric']['precipRate'] = $weather['precipitation_rate'];
        $weatherData['observations'][0]['metric']['precipTotal'] = $weather['precipitation_total'];
        $weatherData['observations'][0]['obsTimeLocal'] = now()->toDateTimeString();

        return $weatherData;
    }


    // metric_calculations
    public function convertFahrenheitToCelsius($fahrenheit, int $precision = 2)
    {
        if (is_null($fahrenheit)) {
            return 0;
        }
        $celsius = ($fahrenheit - 32) * (5 / 9);
        return round($celsius, $precision);
    }

    public function convertMphToKph($mph, int $precision = 2)
    {
        if (is_null($mph)) {
            return 0;
        }
        $kph = $mph * 1.609344;
        return round($kph, $precision);
    }

    public function calculateHeatIndex($tempF, $rh)
    {
        if (is_null($tempF) || is_null($rh)) {
            return 0;
        }

        // Heat Index is only applicable for temps >= 80°F
        if ($tempF < 80) {
            return $tempF;
        }

        // Simple formula for low-range consistency
        $hi = 0.5 * ($tempF + 61.0 + (($tempF - 68.0) * 1.2) + ($rh * 0.094));

        // If the simple formula is above 80, use the full Rothfusz regression
        if ($hi >= 80) {
            $hi = -42.379 + 2.04901523 * $tempF + 10.14333127 * $rh
                - 0.22475541 * $tempF * $rh - 0.00683783 * pow($tempF, 2)
                - 0.05481717 * pow($rh, 2) + 0.00122874 * pow($tempF, 2) * $rh
                + 0.00085282 * $tempF * pow($rh, 2) - 0.00000199 * pow($tempF, 2) * pow($rh, 2);

            // Adjustments for specific conditions (NWS Standard)
            if ($rh < 13 && ($tempF >= 80 && $tempF <= 112)) {
                $adjustment = ((13 - $rh) / 4) * sqrt((17 - abs($tempF - 95.0)) / 17);
                $hi -= $adjustment;
            } elseif ($rh > 85 && ($tempF >= 80 && $tempF <= 87)) {
                $adjustment = (($rh - 85) / 10) * ((87 - $tempF) / 5);
                $hi += $adjustment;
            }
        }
        // dd(round($hi, 2));
        return round($hi, 2);
    }

    public function calculateDewPoint($tempF, $rh)
    {
        // Return null if humidity is missing/invalid
        if (is_null($rh) || $rh <= 0)
            return null;

        // 1. Convert Fahrenheit to Celsius
        $T = ($tempF - 32) * 5 / 9;

        // 2. Constants for Magnus-Tetens
        $a = 17.27;
        $b = 237.7;

        // 3. Calculate Alpha
        $alpha = (($a * $T) / ($b + $T)) + log($rh / 100.0);

        // 4. Calculate Dew Point in Celsius
        $dpC = ($b * $alpha) / ($a - $alpha);

        // 5. Convert back to Fahrenheit
        $dpF = ($dpC * 9 / 5) + 32;

        return round($dpF, 1);
    }

    public function getActiveStations()
    {
        $stations = WeatherStation::where('state', 1)
            ->with(['latestObservation', 'location.locationType'])
            ->get();

        $stations->each(function ($station) {
            $station->makeHidden(['id', 'station_id', 'mode', 'key', 'ip', 'location_id', 'created_at', 'updated_at']);
            if ($station->location) {
                $station->location->makeHidden(['id', 'location_type_id', 'created_at', 'updated_at']);
                if ($station->location->locationType) {
                    $station->location->locationType->makeHidden(['id', 'created_at', 'updated_at']);
                }
            }
            if ($station->latestObservation) {
                $station->latestObservation->makeHidden(['id', 'created_at', 'updated_at']);
            }
        });

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    }
}