<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\WeatherStation;
use App\Models\WeatherStationObservationData;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WundergroundIntegrationController extends Controller
{
    public function index(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-read');
        set_time_limit(300);
        ini_set('memory_limit', '512M');

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

    public function import(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('can-create');
        $request->validate([
            'station_id' => 'required',
            'rows' => 'required|array',
        ]);

        $stationIdentifier = $request->input('station_id');
        $station = WeatherStation::where('station_id', $stationIdentifier)
            ->orWhere('id', $stationIdentifier)
            ->first();

        if (!$station) {
            return response()->json(['error' => 'Weather station not found.'], 404);
        }

        $targetId = $station->id;
        $data = $request->input('rows');

        $numericFields = [
            'temperature',
            'heat_index',
            'dewpoint',
            'humidity',
            'wind_speed',
            'wind_direction',
            'wind_gust',
            'pressure',
            'precipitation_rate',
            'precipitation_total',
            'uv',
            'solar_radiation',
        ];
        $nullableFields = ['uv', 'solar_radiation'];

        foreach ($data as $row) {
            $normalized = [];
            foreach ($row as $key => $val) {
                $normalized[strtolower(trim($key))] = $val;
            }

            $dateTimeVal = $normalized['date_time']
                ?? $normalized['date']
                ?? $normalized['datetime']
                ?? $normalized['timestamp']
                ?? now()->toDateTimeString();

            $cleanedRow = [
                'weather_station_id' => $targetId,
                'date_time' => $dateTimeVal,
            ];

            foreach ($numericFields as $field) {
                if (array_key_exists($field, $normalized)) {
                    $val = $normalized[$field];
                    $default = in_array($field, $nullableFields) ? null : 0;
                    $cleanedRow[$field] = $this->cleanNumeric($val, $default);
                } else {
                    if (!in_array($field, $nullableFields)) {
                        $cleanedRow[$field] = 0;
                    }
                }
            }

            WeatherStationObservationData::create($cleanedRow);
        }

        return response()->json(['message' => 'Data imported successfully.']);
    }

    private function cleanNumeric($value, $default = 0)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_numeric($value)) {
            return $value + 0;
        }
        if (is_string($value)) {
            $cleaned = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value)));
            if ($cleaned !== '' && is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }
        return $default;
    }
}
