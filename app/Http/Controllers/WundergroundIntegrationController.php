<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\WeatherStation;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WundergroundIntegrationController extends Controller
{
    public function index(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');

        $stations = WeatherStation::whereNotNull('station_id')
            ->where('station_id', '!=', '')
            ->get(['id', 'name', 'station_id', 'key']);

        $selectedStationId = $request->input('station_id');
        if (!$selectedStationId && $stations->isNotEmpty()) {
            $selectedStationId = $stations->first()->station_id;
        }

        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $tableData = [];
        $error = null;

        // Fetch WunderGround API key from SystemSetting with fallback
        $apiKeySetting = SystemSetting::where('name', 'api_key')->first()?->value;
        $apiKey = 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d';
        if (is_array($apiKeySetting) && !empty($apiKeySetting['key'])) {
            $apiKey = $apiKeySetting['key'];
        } elseif (is_string($apiKeySetting) && !empty($apiKeySetting)) {
            $apiKey = $apiKeySetting;
        }

        if ($selectedStationId) {
            try {
                $weatherService = new WeatherService($selectedStationId, $apiKey);
                $tableData = $weatherService->getPwsTableData($startDate, $endDate);
                if (empty($tableData) || (is_object($tableData) && $tableData->isEmpty())) {
                    $error = "No observation data returned for station {$selectedStationId}.";
                }
            } catch (\Exception $e) {
                logger()->error("Wunderground Integration Error: " . $e->getMessage());
                $error = "Failed to fetch weather data: " . $e->getMessage();
            }
        }

        return Inertia::render('LFEWS/WunderGroundIntegration', [
            'stations' => $stations,
            'filters' => [
                'station_id' => $selectedStationId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'tableData' => $tableData,
            'apiKeyUsed' => $apiKey,
            'error' => $error,
        ]);
    }
}
