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
        $stations = WeatherStation::with(['location', 'location.locationType'])->get();
        // Assuming we want to show location description in the table, we fetch it with relation.
        
        return Inertia::render('WeatherStations', [
            'stations' => $stations,
            'showCreateModal' => false,
            'showEditModal' => false,
            'activeCount' => $stations->where('state', 1)->count(), // Changed to integer check
            'inactiveCount' => $stations->where('state', 0)->count(), // Changed to integer check
            'maintenanceCount' => $stations->where('state', 2)->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stations = WeatherStation::with(['location', 'location.locationType'])->get();
        $locations = Location::with('locationType')->get(); // Fetch locations for dropdown

        return Inertia::render('WeatherStations', [
            'stations' => $stations,
            'locations' => $locations, // Pass locations
            'showCreateModal' => true,
            'showEditModal' => false,
            'activeCount' => $stations->where('state', 1)->count(),
            'inactiveCount' => $stations->where('state', 0)->count(),
            'maintenanceCount' => $stations->where('state', 2)->count(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        $station = WeatherStation::find($id);

        if (!$station) {
            return redirect()->back()->with('error', 'Weather station not found.');
        }
        $location = Location::find($station->location_id);
        $station->location = ['latitude' => $location->latitude, 'longitude' => $location->longitude];
        $stations = WeatherStation::with(['location', 'location.locationType'])->get();
        $locations = Location::with('locationType')->get();

        return Inertia::render('WeatherStations', [
            'stations' => $stations,
            'locations' => $locations,
            'editingStation' => $station,
            'showCreateModal' => false,
            'activeCount' => $stations->where('state', 1)->count(),
            'inactiveCount' => $stations->where('state', 0)->count(),
            'maintenanceCount' => $stations->where('state', 2)->count(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WeatherStation $weatherStation)
    {
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
        try {
            $weatherStation->delete();
            return redirect()->route('weather-stations')->with('success', 'Weather station deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete weather station: ' . $e->getMessage());
        }
    }

 
    public function pullObservationData()
    {
       // Simple lock to prevent concurrent Wunderground api pulls
        $lockKey = 'wunderground_pull_lock';
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            if (request()->expectsJson() || !request()->ajax()) {
                return ['error' => 'Data pull is already in progress.'];
            }
            return redirect()->back()->with('warning', 'Data pull is already in progress. Please try again later.');
        }

        try {
            \Illuminate\Support\Facades\Cache::put($lockKey, true, 30); // 30 second lock

            $stations = WeatherStation::all();
            $observations = [];
            foreach ($stations as $station) {
                try {
                    if($station->mode === 'API/wunderground') {
                        $weatherData = $this->wundergroundAPI($station);
                    }
                    // dd($weatherData);
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
                            'wind_speed' => $weatherData['observations'][0]['metric']['windSpeed'],
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
            \Illuminate\Support\Facades\Cache::put('latest_wunderground_api_data', $observations, 60);
            
            //Update history
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

                    // Keep only last 50 points
                    if (count($history[$stationId]) > 50) {
                        array_shift($history[$stationId]);
                    }
                }
            }
            \Illuminate\Support\Facades\Cache::put('wunderground_api_history', $history, 1440); // 24 hours

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

    public function wundergroundAPI($station)
    {
        $response = Http::get('https://api.weather.com/v2/pws/observations/current', [
                                'stationId' => $station->station_id,
                                'format' => 'json',
                                'units' => 'm',
                                'numericPrecision' => 'decimal',
                                'apiKey' => 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d',
                            ]);
        return $response->json();
    }
}
