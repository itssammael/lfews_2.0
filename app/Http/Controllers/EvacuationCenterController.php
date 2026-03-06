<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EvacuationCenterController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');

        $evacuationCenters = $this->fetchEvacuationCentersData();

        return Inertia::render('LFEWS/EvacuationCenter', [
            'evacuationCenters' => $evacuationCenters,
        ]);
    }

    public function getEvacuationCenters()
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');

        $fetch = $this->fetchEvacuationCentersData();
        // dd($fetch);
        \Illuminate\Support\Facades\Cache::put('evacuation_centers', $fetch, 60 * 24 * 7);
        $data = \Illuminate\Support\Facades\Cache::get('evacuation_centers');

        return response()->json([
            'data' => $data
        ]);
    }

    private function fetchEvacuationCentersData()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get('https://swera.bayawancity.gov.ph/fetch/evacs');
            
            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? $data : [];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Fetch Evacuation Centers Error: ' . $e->getMessage());
        }

        // Return empty array if request fails
        return [];
    }
}
