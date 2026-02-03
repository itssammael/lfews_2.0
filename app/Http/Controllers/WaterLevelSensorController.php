<?php

namespace App\Http\Controllers;

use App\Services\ModbusService;
use App\Models\WaterLevelSensor;
use Illuminate\Http\Request;

use App\Models\Location;
use App\Models\WaterLevelSensorData;
use Carbon\Carbon;

class WaterLevelSensorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sensors = WaterLevelSensor::with(['location', 'location.locationType'])->get();
        // Assuming we want to show location description in the table, we fetch it with relation.
        
        return \Inertia\Inertia::render('WaterLevelSensors', [
            'sensors' => $sensors,
            'showCreateModal' => false,
            'showEditModal' => false,
            'activeCount' => $sensors->where('state', 1)->count(),
            'inactiveCount' => $sensors->where('state', 0)->count(),
            'maintenanceCount' => $sensors->where('state', 2)->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sensors = WaterLevelSensor::with(['location', 'location.locationType'])->get();
        $locations = Location::with('locationType')->get(); // Fetch locations for dropdown if needed or just for data

        return \Inertia\Inertia::render('WaterLevelSensors', [
            'sensors' => $sensors,
            'locations' => $locations,
            'showCreateModal' => true,
            'showEditModal' => false,
            'activeCount' => $sensors->where('state', 1)->count(),
            'inactiveCount' => $sensors->where('state', 0)->count(),
            'maintenanceCount' => $sensors->where('state', 2)->count(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'mode' => 'required|string|max:255',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'level_2' => 'required|numeric',
            'level_3' => 'required|numeric',
            'level_4' => 'required|numeric',
            'state' => 'required|integer',
            'ip' => 'required|string|max:255',
            'port' => 'required|integer',
            'slave_id' => 'required|integer',
        ]);

        try {
            $location = Location::create([
                'latitude' => $validated['lat'],
                'longitude' => $validated['long'],
                'location_type_id' => 2, // location_type_id = 2 for Device Sensors
            ]);
            
            WaterLevelSensor::create([
                'name' => $validated['name'],
                'brand' => $validated['brand'],
                'mode' => $validated['mode'],
                'level_2' => $validated['level_2'],
                'level_3' => $validated['level_3'],
                'level_4' => $validated['level_4'],
                'state' => $validated['state'],
                'ip' => $validated['ip'],
                'port' => $validated['port'],
                'slave_id' => $validated['slave_id'],
                'location_id' => $location->id,
            ]);
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

        // Attach location coords to sensor object for edit form
        $location = Location::find($sensor->location_id);
        if ($location) {
             $sensor->location = ['latitude' => $location->latitude, 'longitude' => $location->longitude];
        }

        $sensors = WaterLevelSensor::with(['location', 'location.locationType'])->get();
        $locations = Location::with('locationType')->get();

        return \Inertia\Inertia::render('WaterLevelSensors', [
            'sensors' => $sensors,
            'locations' => $locations,
            'editingSensor' => $sensor,
            'showCreateModal' => false,
            'activeCount' => $sensors->where('state', 1)->count(),
            'inactiveCount' => $sensors->where('state', 0)->count(),
            'maintenanceCount' => $sensors->where('state', 2)->count(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WaterLevelSensor $waterLevelSensor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'mode' => 'required|string|max:255',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'level_2' => 'required|numeric',
            'level_3' => 'required|numeric',
            'level_4' => 'required|numeric',
            'state' => 'required|integer',
            'ip' => 'required|string|max:255',
            'port' => 'required|integer',
            'slave_id' => 'required|integer',
            'location_id' => 'required|exists:locations,id',
        ]);

        try {
            $location = Location::find($validated['location_id']);
            if ($location) {
                $location->update([
                    'latitude' => $validated['lat'],
                    'longitude' => $validated['long'],
                ]);
            }

            $waterLevelSensor->update([
                 'name' => $validated['name'],
                'brand' => $validated['brand'],
                'mode' => $validated['mode'],
                'level_2' => $validated['level_2'],
                'level_3' => $validated['level_3'],
                'level_4' => $validated['level_4'],
                'state' => $validated['state'],
                'ip' => $validated['ip'],
                'port' => $validated['port'],
                'slave_id' => $validated['slave_id'],
            
            ]);
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
        try {
            $waterLevelSensor->delete();
            return redirect()->route('water-level-sensors')->with('success', 'Water level sensor deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete water level sensor: ' . $e->getMessage());
        }
    }


    public function pullWaterData(ModbusService $modbusService)
    {
        // Simple lock to prevent concurrent Modbus pulls
        $lockKey = 'modbus_pull_lock';
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            $message = 'A data pull is already in progress.';
            if (request()->ajax() || request()->wantsJson()) {
                return ['error' => $message];
            }
            return redirect()->back()->with('warning', $message);
        }

        try {
            \Illuminate\Support\Facades\Cache::put($lockKey, true, 30); // 30 second lock
            
            // Releasing session lock allows other requests to proceed while we wait for I/O (Modbus)
            if (session_id()) {
                session_write_close();
            }
            set_time_limit(60);

            $sensors = \App\Models\WaterLevelSensor::all();
            $results = [];

            foreach ($sensors as $sensor) {
                try {
                    $data = $modbusService->readModbusData(
                        $sensor->ip,
                        (int)$sensor->port,
                        1,
                        6,
                        (int)$sensor->slave_id,
                        1.5
                    );

                    $results[$sensor->id] = [
                        'sensor_id' => $sensor->id,
                        'name' => $sensor->name,
                        'success' => true,
                        'data' => $data[5]/ 10,
                        'timestamp' => now()->toDateTimeString(),
                    ];
                   
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

            // Update latest data
            \Illuminate\Support\Facades\Cache::put('latest_modbus_data', $results, 60);

            // Update history
            $history = \Illuminate\Support\Facades\Cache::get('modbus_history', []);
            
            if (empty($history)) {
                $todayData = WaterLevelSensorData::whereDate('date', Carbon::today())
                    ->orderBy('date', 'asc')
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
                    
                    // Keep only last 50 points per sensor
                    if (count($history[$sensorId]) > 50) {
                        array_shift($history[$sensorId]);
                    }
                }
            }
            
            \Illuminate\Support\Facades\Cache::put('modbus_history', $history, 1440); // 24 hours

            \Illuminate\Support\Facades\Cache::forget($lockKey);

            return $results;
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
}
