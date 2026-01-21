<?php

namespace App\Http\Controllers;

use App\Models\WaterLevelSensor;
use Illuminate\Http\Request;

class WaterLevelSensorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sensors = WaterLevelSensor::all();
        
        return \Inertia\Inertia::render('WaterLevelSensors', [
            'sensors' => $sensors,
            'showCreateModal' => false,
            'showEditModal' => false,
            'activeCount' => $sensors->where('state', '1')->count(),
            'inactiveCount' => $sensors->where('state', '0')->count(),
            'maintenanceCount' => $sensors->where('state', '2')->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sensors = WaterLevelSensor::all();
        return \Inertia\Inertia::render('WaterLevelSensors', [
            'sensors' => $sensors,
            'showCreateModal' => true,
            'showEditModal' => false,
            'activeCount' => $sensors->where('state', '1')->count(),
            'inactiveCount' => $sensors->where('state', '0')->count(),
            'maintenanceCount' => $sensors->where('state', '2')->count(),
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
            WaterLevelSensor::create($validated);
            return redirect()->route('water-level-sensors')->with('success', 'Water level sensor created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create water level sensor: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(WaterLevelSensor $waterLevelSensor)
    {
        //
    }

    public function edit(string $id)
    {
        $sensor = WaterLevelSensor::find($id);

        if (!$sensor) {
            return redirect()->back()->with('error', 'Water level sensor not found.');
        }

        $sensors = WaterLevelSensor::all();

        return \Inertia\Inertia::render('WaterLevelSensors', [
            'sensors' => $sensors,
            'editingSensor' => $sensor,
            'showCreateModal' => false,
            'activeCount' => $sensors->where('state', '1')->count(),
            'inactiveCount' => $sensors->where('state', '0')->count(),
            'maintenanceCount' => $sensors->where('state', '2')->count(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WaterLevelSensor $waterLevelSensor)
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
            $waterLevelSensor->update($validated);
            return redirect()->route('water-level-sensors')->with('success', 'Water level sensor updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update water level sensor: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WaterLevelSensor $waterLevelSensor)
    {
        //
    }
}
