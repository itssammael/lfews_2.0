<?php

namespace App\Http\Controllers;

use App\Models\WeatherStation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeatherStationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stations = WeatherStation::all();
        
        return Inertia::render('WeatherStations', [
            'stations' => $stations,
            'showCreateModal' => false,
            'showEditModal' => false,
            'activeCount' => $stations->where('state', '1')->count(),
            'inactiveCount' => $stations->where('state', '0')->count(),
            'maintenanceCount' => $stations->where('state', '2')->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stations = WeatherStation::all();
        return Inertia::render('WeatherStations', [
            'stations' => $stations,
            'showCreateModal' => true,
            'showEditModal' => false,
            'activeCount' => $stations->where('state', '1')->count(),
            'inactiveCount' => $stations->where('state', '0')->count(),
            'maintenanceCount' => $stations->where('state', '2')->count(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'level_2' => 'nullable|numeric',
            'level_3' => 'nullable|numeric',
            'level_4' => 'nullable|numeric',
            'state' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:255',
            'port' => 'nullable|integer',
            'slave_id' => 'nullable|integer',
        ]);

        try {
            WeatherStation::create($validated);
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

        $stations = WeatherStation::all();

        return Inertia::render('WeatherStations', [
            'stations' => $stations,
            'editingStation' => $station,
            'showCreateModal' => false,
            'activeCount' => $stations->where('state', '1')->count(),
            'inactiveCount' => $stations->where('state', '0')->count(),
            'maintenanceCount' => $stations->where('state', '2')->count(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WeatherStation $weatherStation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'level_2' => 'nullable|numeric',
            'level_3' => 'nullable|numeric',
            'level_4' => 'nullable|numeric',
            'state' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:255',
            'port' => 'nullable|integer',
            'slave_id' => 'nullable|integer',
        ]);

        try {
            $weatherStation->update($validated);
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
