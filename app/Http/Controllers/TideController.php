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
        $url = "https://www.tide-forecast.com/osm/points_of_interest.json?bbox=120.86334228515625,8.876930702774121,124.13177490234376,9.933682229573083";

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $locations = $data['locations'] ?? (is_array($data) ? $data : []);

                $targetStation = null;
                foreach ($locations as $st) {
                    if (isset($st['name']) && (str_contains($st['name'], 'Bayawan City') || str_contains($st['name'], 'Bayawan'))) {
                        $targetStation = $st;
                        break;
                    }
                }
                if (!$targetStation && !empty($locations)) {
                    $targetStation = $locations[0];
                }

                if ($targetStation && isset($targetStation['days'])) {
                    $lat = $targetStation['lat'] ?? 9.3666;
                    $lon = $targetStation['lon'] ?? 122.8;
                    $station = $targetStation['name'] ?? 'Bayawan City';

                    $extremes = [];
                    foreach ($targetStation['days'] as $day) {
                        if (isset($day['tides']) && is_array($day['tides'])) {
                            foreach ($day['tides'] as $tide) {
                                $dt = (int)$tide['timestamp'];
                                $height = (float)$tide['height'];
                                $type = ucfirst(strtolower($tide['type']));
                                $dateStr = date('Y-m-d H:i:s', $dt);

                                Tide::updateOrCreate(
                                    ['dt' => $dt],
                                    [
                                        'date' => $dateStr,
                                        'height' => $height,
                                        'type' => $type,
                                        'latitude' => $lat,
                                        'longitude' => $lon,
                                        'station' => $station,
                                    ]
                                );

                                $extremes[] = [
                                    'dt' => $dt,
                                    'date' => $dateStr,
                                    'height' => $height,
                                    'type' => $type,
                                ];
                            }
                        }
                    }

                    // Sort extremes chronologically
                    usort($extremes, fn($a, $b) => $a['dt'] <=> $b['dt']);

                    // Generate continuous interpolated heights for smooth wave rendering
                    for ($i = 0; $i < count($extremes) - 1; $i++) {
                        $e1 = $extremes[$i];
                        $e2 = $extremes[$i + 1];
                        $dt1 = $e1['dt'];
                        $dt2 = $e2['dt'];
                        $h1 = $e1['height'];
                        $h2 = $e2['height'];

                        if ($dt2 > $dt1) {
                            for ($t = $dt1; $t < $dt2; $t += 1800) {
                                $ratio = ($t - $dt1) / ($dt2 - $dt1);
                                $interpolatedH = $h1 + ($h2 - $h1) * (1 - cos($ratio * M_PI)) / 2;

                                \App\Models\TideHeight::updateOrCreate(
                                    ['dt' => $t],
                                    [
                                        'date' => date('Y-m-d H:i:s', $t),
                                        'height' => round($interpolatedH, 3),
                                        'latitude' => $lat,
                                        'longitude' => $lon,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Tide Sync Failed: " . $e->getMessage());
        }

        return redirect()->back();
    }
}
