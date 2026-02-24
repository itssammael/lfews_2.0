<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FloodRiskController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('LFEWS/FloodHazardMap', [
            'floodRisks' => \App\Models\FloodRisk::query()
                ->when(request('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate(10)
                ->withQueryString(),
            'filters' => request()->only(['search'])
        ]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', \App\Models\FloodRisk::class);
        $request->validate([
            'name' => 'required|string',
            'properties' => 'nullable|array',
            'geometry' => 'required',
        ]);

        \App\Models\FloodRisk::create($request->all());

        return redirect()->route('flood_risks.index')->with('success', 'Flood risk area created successfully.');
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\FloodRisk $floodRisk)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $floodRisk);
        $request->validate([
            'name' => 'required|string',
            'properties' => 'nullable|array',
            'geometry' => 'required',
        ]);

        $floodRisk->update($request->all());

        return redirect()->route('flood_risks.index')->with('success', 'Flood risk area updated successfully.');
    }

    public function destroy(\App\Models\FloodRisk $floodRisk)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $floodRisk);
        $floodRisk->delete();
        return redirect()->route('flood_risks.index')->with('success', 'Flood risk area deleted successfully.');
    }
}
