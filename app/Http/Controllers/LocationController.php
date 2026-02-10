<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-data');
        $locationTypes = LocationType::all();

        return Inertia::render('Locations/Create', [
            'locationTypes' => $locationTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-data');
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_type_id' => 'required|exists:location_types,id',
        ]);

        try {
            Location::create($validated);
            return redirect()->back()->with('success', 'Location created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create location: ' . $e->getMessage());
        }
    }
}
