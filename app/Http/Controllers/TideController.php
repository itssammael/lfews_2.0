<?php

namespace App\Http\Controllers;

use App\Models\Tide;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class TideController extends Controller
{
    public function index()
    {
        // Get tides from now onwards (or keep some history)
        $tides = Tide::where('dt', '>=', time() - 86400) // Keep 24h history
            ->orderBy('dt', 'asc')
            ->get();

        // If no future tides, sync
        if ($tides->where('dt', '>=', time())->isEmpty()) {
            $this->syncTides();
            $tides = Tide::where('dt', '>=', time() - 86400)
                ->orderBy('dt', 'asc')
                ->get();
        }

        return Inertia::render('LFEWS/LunarTides', [
            'tides' => $tides,
        ]);
    }

    public function syncTides()
    {
        $lat = 9.3668;
        $lon = 122.8055;
        $key = '050aba25-4e85-42f0-a856-cba4dab9822a';

        try {
            // Fetch extremes (High/Low) AND heights
            $response = Http::timeout(30)->withoutVerifying()->get("https://www.worldtides.info/api/v3", [
                'extremes' => '',
                'heights' => '',
                'lat' => $lat,
                'lon' => $lon,
                'key' => $key,
                'days' => 7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $station = $data['station'] ?? 'Nearest Station';

                if (isset($data['extremes'])) {
                    foreach ($data['extremes'] as $extreme) {
                        Tide::updateOrCreate(
                            ['dt' => $extreme['dt']],
                            [
                                'date' => $extreme['date'],
                                'height' => $extreme['height'],
                                'type' => $extreme['type'],
                                'latitude' => $lat,
                                'longitude' => $lon,
                                'station' => $station,
                            ]
                        );
                    }
                }

                if (isset($data['heights'])) {
                    foreach ($data['heights'] as $height) {
                        \App\Models\TideHeight::updateOrCreate(
                            ['dt' => $height['dt']],
                            [
                                'date' => $height['date'],
                                'height' => $height['height'],
                                'latitude' => $lat,
                                'longitude' => $lon,
                            ]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Tide Sync Failed: " . $e->getMessage());
        }

        return redirect()->back();
    }
}
