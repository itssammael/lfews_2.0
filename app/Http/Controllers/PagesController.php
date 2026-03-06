<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SystemSetting;

class PagesController extends Controller
{
    public function index()
    {
        return Inertia::render('Guest/Home');
    }

    public function awards()
    {
        return Inertia::render('Guest/Awards');
    }

    public function services()
    {
        return Inertia::render('Guest/Services');
    }

    public function heatIndexMap()
    {
        $stations = \App\Models\WeatherStation::with('location')->get();
        
        $latestWeatherData = [];
        foreach ($stations as $station) {
            $latestEntry = \App\Models\WeatherStationObservationData::where('weather_station_id', $station->id)
                ->orderBy('date_time', 'desc')
                ->first();

            if ($latestEntry) {
                $latestWeatherData[$station->id] = [
                    'id' => $station->id,
                    'station_id' => $station->station_id,
                    'name' => $station->name,
                    'success' => true,
                    'data' => [
                        'heat_index' => $latestEntry->heat_index,
                        'date_time' => $latestEntry->date_time,
                    ],
                    'timestamp' => $latestEntry->created_at->toDateTimeString(),
                ];
            }
        }

        return Inertia::render('Guest/HeatIndexMap', [
            'stations' => $stations,
            'latestWeatherData' => $latestWeatherData,
        ]);
    }

    public function systemSettings()
    {
        $settings = SystemSetting::with('updatedBy')->get()->pluck('value', 'name');
        return Inertia::render('SystemSettings', [
            'settings' => $settings,
        ]);
    }

    public function updateSystemSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'value' => 'required',
        ]);

        $value = $validated['value'];

        // Handle file uploads if the value is a file (e.g., from Branding configuration)
        if ($request->hasFile('value')) {
            $file = $request->file('value');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('system'), $filename);
            $value = '/system/' . $filename;
        }

        SystemSetting::updateOrCreate(
            ['name' => $validated['name']],
            [
                'value' => $value,
                'updated_by' => auth()->id(),
            ]
        );

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
