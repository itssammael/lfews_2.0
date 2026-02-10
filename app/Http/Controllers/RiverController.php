<?php

namespace App\Http\Controllers;

use App\Models\River;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RiverController extends Controller
{
    public function index()
    {
        $rivers = River::all();
        return Inertia::render('Rivers/Index', [
            'rivers' => $rivers
        ]);
    }



    public function store(Request $request)
    {
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
        $river->delete();
        return redirect()->route('rivers.index')->with('success', 'River deleted successfully.');
    }
}
