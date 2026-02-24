<?php

namespace App\Http\Controllers;

use App\Models\Contour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HazardMapController extends Controller
{
    public function index()
    {
        return Inertia::render('LFEWS/HazardMap', [
            'contours' => Contour::query()
                ->when(request('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate(5)
                ->withQueryString(),
            'filters' => request()->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $request->validate([
            'name' => 'required|string',
            'properties' => 'nullable|array',
            'geometry' => 'required',
        ]);

        Contour::create($request->all());

        return redirect()->route('hazard-map.index')->with('success', 'Contour added successfully.');
    }

    public function update(Request $request, Contour $hazard_map)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-update');
        $request->validate([
            'name' => 'required|string',
            'properties' => 'nullable|array',
            'geometry' => 'required',
        ]);

        $hazard_map->update($request->all());

        return redirect()->route('hazard-map.index')->with('success', 'Contour updated successfully.');
    }

    public function destroy(Contour $hazard_map)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-delete');
        $hazard_map->delete();
        return redirect()->route('hazard-map.index')->with('success', 'Contour deleted successfully.');
    }
}
