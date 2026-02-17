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
        // return \Illuminate\Support\Facades\Cache::remember('evacuation_centers', 60 * 24 * 7, function () {
        // Logic to fetch data if cache is empty
        // In a real application, this might come from a database.
        // For now, we return the static data as requested.
        return [
            [
                'id' => 1,
                'name' => "BCSTEC HS",
                'address' => "Cabcabon, Banga",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 2,
                'name' => "BNHS SHS",
                'address' => "Villareal",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 3,
                'name' => "BNHS JHS",
                'address' => "Villareal",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 4,
                'name' => "Villareal Gymnasium",
                'address' => "Balabag, Villareal",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 5,
                'name' => "Boyco Gymnasium",
                'address' => "Boyco",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 6,
                'name' => "Boyco Gymnasium",
                'address' => "Boyco",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 7,
                'name' => "Boyco Gymnasium",
                'address' => "Boyco",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
            [
                'id' => 8,
                'name' => "Boyco Gymnasium",
                'address' => "Boyco",
                'latitude' => "12.3456",
                'longitude' => "12.3456",
                'max_capacity' => "1000",
                'curren_resident' => "500",
            ],
        ];
    }
}
