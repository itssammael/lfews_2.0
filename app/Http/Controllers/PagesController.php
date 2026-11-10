<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SystemSetting;

class PagesController extends Controller
{

    public function localWeatherMap()
    {
        $stations = \App\Models\WeatherStation::where('state', 1)->with('location')->get();
        
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
                        'temperature' => $latestEntry->temperature,
                        'humidity' => $latestEntry->humidity,
                        'wind_speed' => $latestEntry->wind_speed,
                        'wind_direction' => $latestEntry->wind_direction,
                        'precipitation_rate' => $latestEntry->precipitation_rate,
                        'solar_radiation' => $latestEntry->solar_radiation,
                        'uv' => $latestEntry->uv,
                        'date_time' => $latestEntry->date_time,
                    ],
                    'timestamp' => $latestEntry->created_at->toDateTimeString(),
                ];
            }
        }

        $barangays = \App\Models\Barangay::all();
        $hiSettings = SystemSetting::where('name', 'heat_index_advisory_gauge')->first()?->value ?? [];
        
        return Inertia::render('Guest/LocalWeatherMap', [
            'stations' => $stations,
            'latestWeatherData' => $latestWeatherData,
            'barangays' => $barangays,
            'hiSettings' => $hiSettings,
        ]);
    }

    public function systemSettings()
    {
        \Illuminate\Support\Facades\Gate::authorize('admin-only');
        $settings = SystemSetting::with('updatedBy')->get()->pluck('value', 'name');
        return Inertia::render('SystemSettings', [
            'settings' => $settings,
        ]);
    }

    public function updateSystemSettings(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('admin-only');

        $rules = [
            'name' => 'required|string|max:191',
            'value' => 'required',
        ];

        if ($request->hasFile('value')) {
            $rules['value'] = 'required|file|mimes:jpg,jpeg,png,webp,ico|max:2048';
        }

        $validated = $request->validate($rules);

        $value = $validated['value'];

        // Handle file uploads if the value is a file (e.g., from Branding configuration)
        if ($request->hasFile('value')) {
            $file = $request->file('value');
            $filename = time() . '_' . $file->hashName();
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
        \Illuminate\Support\Facades\Cache::forget('shared_system_settings');
        
        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function updateGeoData()
    {
        return Inertia::render('LFEWS/UpdateGeoData');
    }
}
