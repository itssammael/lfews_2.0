<?php

namespace App\Http\Controllers;

use App\Models\River;
use App\Models\Contour;
use App\Models\FloodRisk;
use App\Models\Barangay;
use App\Models\Sitio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GeoDataController extends Controller
{
    public function fetchCurrentData($type)
    {
        Gate::authorize('can-read');

        $data = [];
        switch ($type) {
            case 'river':
                $data = River::all();
                break;
            case 'contour':
                $data = Contour::all();
                break;
            case 'flood_hazard':
                $data = FloodRisk::all();
                break;
            case 'barangay':
                $data = Barangay::all();
                break;
            case 'sitio':
                $data = Sitio::all();
                break;
            default:
                return response()->json(['error' => 'Invalid data type'], 400);
        }

        $features = $data->map(function ($item) use ($type) {
            return [
                'type' => 'Feature',
                'properties' => $item->properties ?? (object)[],
                'geometry' => $item->geometry,
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function update(Request $request)
    {
        Gate::authorize('can-create');

        $request->validate([
            'type' => 'required|string|in:river,contour,flood_hazard,barangay,sitio',
            'geojson' => 'required|array',
            'geojson.features' => 'required|array',
        ]);

        $type = $request->input('type');
        $features = $request->input('geojson')['features'];

        try {
            DB::beginTransaction();

            switch ($type) {
                case 'river':
                    River::query()->delete();
                    foreach ($features as $feature) {
                        River::create([
                            'name' => $feature['properties']['seg_name'] ?? ($feature['properties']['name'] ?? 'Unknown River'),
                            'properties' => $feature['properties'] ?? [],
                            'geometry' => $feature['geometry'],
                        ]);
                    }
                    break;
                case 'contour':
                    Contour::query()->delete();
                    foreach ($features as $feature) {
                        Contour::create([
                            'name' => $feature['properties']['name'] ?? 'Contour Line',
                            'properties' => $feature['properties'] ?? [],
                            'geometry' => $feature['geometry'],
                        ]);
                    }
                    break;
                case 'flood_hazard':
                    FloodRisk::query()->delete();
                    foreach ($features as $feature) {
                        FloodRisk::create([
                            'name' => $feature['properties']['name'] ?? 'Flood Hazard Area',
                            'properties' => $feature['properties'] ?? [],
                            'geometry' => $feature['geometry'],
                        ]);
                    }
                    break;
                case 'barangay':
                    Barangay::query()->delete();
                    foreach ($features as $feature) {
                        Barangay::create([
                            'name' => $feature['properties']['barangay'] ?? ($feature['properties']['name'] ?? 'Unknown Barangay'),
                            'properties' => $feature['properties'] ?? [],
                            'geometry' => $feature['geometry'],
                        ]);
                    }
                    break;
                case 'sitio':
                    Sitio::query()->delete();
                    foreach ($features as $feature) {
                        Sitio::create([
                            'name' => $feature['properties']['sitioname'] ?? ($feature['properties']['name'] ?? 'Unknown Sitio'),
                            'barangay_name' => $feature['properties']['barangayname'] ?? null,
                            'barangay_id' => $feature['properties']['brgy_id'] ?? null,
                            'properties' => $feature['properties'] ?? [],
                            'geometry' => $feature['geometry'],
                        ]);
                    }
                    break;
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $type)) . ' data updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update data: ' . $e->getMessage()], 500);
        }
    }
}
