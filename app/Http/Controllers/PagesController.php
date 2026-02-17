<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SystemSetting;

class PagesController extends Controller
{
    public function index()
    {
        return Inertia::render('Guest/Home');
    }

    public function awards()
    {
        return Inertia::render('Guest/Awards');
    }

    public function services()
    {
        return Inertia::render('Guest/Services');
    }

    public function systemSettings()
    {
        $settings = SystemSetting::with('updatedBy')->get()->pluck('value', 'name');
        return Inertia::render('SystemSettings', [
            'settings' => $settings,
        ]);
    }

    public function updateSystemSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'value' => 'required',
        ]);

        SystemSetting::updateOrCreate(
            ['name' => $validated['name']],
            [
                'value' => $validated['value'],
                'updated_by' => auth()->id(),
            ]
        );

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
