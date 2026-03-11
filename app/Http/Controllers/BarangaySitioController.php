<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Sitio;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BarangaySitioController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $barangays = Barangay::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->withQueryString();

        $sitios = Sitio::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('barangay_name', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->withQueryString();

        return Inertia::render('LFEWS/BarangaysSitios', [
            'barangays' => $barangays,
            'sitios' => $sitios,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function getGeoJson()
    {
        $barangays = Barangay::all();
        $sitios = Sitio::all();

        return response()->json([
            'barangays' => $barangays,
            'sitios' => $sitios
        ]);
    }
}
