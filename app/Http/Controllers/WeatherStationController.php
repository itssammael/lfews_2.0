<?php

namespace App\Http\Controllers;

use App\Models\WeatherStation;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'state' => 'required|integer', // Changed to integer validation
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ]);

        try {
            $location = Location::create([
                'latitude' => $validated['lat'],
                'longitude' => $validated['long'],
                'location_type_id' => 3,
            ]);
            WeatherStation::create([
                'name' => $validated['name'],
                'station_id' => $validated['station_id'],
                'mode' => $validated['mode'],
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
                'state' => $validated['state'],
                'location_id' => $validated['location_id'],
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
}
