<?php

namespace App\Http\Controllers;

use App\Models\River;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RiverController extends Controller
{
    public function index()
    {
        return Inertia::render('Rivers/Index', [
            'rivers' => River::query()
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
        \Illuminate\Support\Facades\Gate::authorize('manage-data');
        $request->validate([
            'name' => 'required|string',
            'properties' => 'nullable|array',
            'geometry' => 'required', // can be array or string depending on format
        ]);

        River::create($request->all());

        return redirect()->route('rivers.index')->with('success', 'River created successfully.');
    }

    public function update(Request $request, River $river)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-data');
        $request->validate([
            'name' => 'required|string',
            'properties' => 'nullable|array',
            'geometry' => 'required',
        ]);

        $river->update($request->all());

        return redirect()->route('rivers.index')->with('success', 'River updated successfully.');
    }

    public function destroy(River $river)
    {
        \Illuminate\Support\Facades\Gate::authorize('admin-only');
        $river->delete();
        return redirect()->route('rivers.index')->with('success', 'River deleted successfully.');
    }
}
