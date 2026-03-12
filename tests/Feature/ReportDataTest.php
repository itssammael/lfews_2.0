<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\WeatherStation;
use App\Models\WeatherStationObservationData;
use App\Models\Location;
use App\Models\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'viewer', 'can_create' => false, 'can_read' => true, 'can_update' => false, 'can_delete' => false]);
    }

    public function test_single_station_rain_report_contains_chart_data()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'viewer')->first());

        $locationType = LocationType::create(['description' => 'Test Location Type']);
        $location = Location::create([
            'latitude' => 0,
            'longitude' => 0,
            'location_type_id' => $locationType->id,
        ]);

        $station = WeatherStation::create([
            'name' => 'Test Station',
            'station_id' => 'TS001',
            'mode' => 'online',
            'location_id' => $location->id,
        ]);

        WeatherStationObservationData::create([
            'weather_station_id' => $station->id,
            'temperature' => 25.0,
            'heat_index' => 26.0,
            'dewpoint' => 20.0,
            'humidity' => 70,
            'wind_speed' => 5.0,
            'wind_direction' => 180,
            'wind_gust' => 10.0,
            'pressure' => 1013.0,
            'precipitation_rate' => 1.5,
            'precipitation_total' => 10.0,
            'uv' => 5,
            'solar_radiation' => 500,
            'date_time' => '2026-02-01 12:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(route('reports.weather-observation-data', [
            'report' => 'Rain',
            'station' => 'Test Station',
            'reportType' => 'Monthly',
            'year' => '2026',
            'month' => 'February',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['records', 'chartData', 'stationNames', 'summaryRecords']);
        
        $this->assertNotEmpty($response->json('chartData'), 'chartData should not be empty for a single station');
    }
    public function test_all_stations_rain_report_contains_chart_data()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'viewer')->first());

        $locationType = LocationType::create(['description' => 'Test Location Type']);
        $location = Location::create([
            'latitude' => 0,
            'longitude' => 0,
            'location_type_id' => $locationType->id,
        ]);

        $station1 = WeatherStation::create([
            'name' => 'Station 1',
            'station_id' => 'S1',
            'mode' => 'online',
            'location_id' => $location->id,
        ]);

        $station2 = WeatherStation::create([
            'name' => 'Station 2',
            'station_id' => 'S2',
            'mode' => 'online',
            'location_id' => $location->id,
        ]);

        WeatherStationObservationData::create([
            'weather_station_id' => $station1->id,
            'temperature' => 25.0,
            'heat_index' => 26.0,
            'dewpoint' => 20.0,
            'humidity' => 70,
            'wind_speed' => 5.0,
            'wind_direction' => 180,
            'wind_gust' => 10.0,
            'pressure' => 1013.0,
            'precipitation_rate' => 1.5,
            'precipitation_total' => 10.0,
            'uv' => 5,
            'solar_radiation' => 500,
            'date_time' => '2026-02-01 12:00:00',
        ]);

        WeatherStationObservationData::create([
            'weather_station_id' => $station2->id,
            'temperature' => 25.0,
            'heat_index' => 26.0,
            'dewpoint' => 20.0,
            'humidity' => 70,
            'wind_speed' => 5.0,
            'wind_direction' => 180,
            'wind_gust' => 10.0,
            'pressure' => 1013.0,
            'precipitation_rate' => 2.0,
            'precipitation_total' => 15.0,
            'uv' => 5,
            'solar_radiation' => 600,
            'date_time' => '2026-02-01 12:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(route('reports.weather-observation-data', [
            'report' => 'Rain',
            'station' => 'All',
            'reportType' => 'Monthly',
            'year' => '2026',
            'month' => 'February',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['records', 'chartData', 'stationNames', 'summaryRecords']);
        
        $chartData = $response->json('chartData');
        $this->assertNotEmpty($chartData);
        $this->assertArrayHasKey('Station 1', $chartData[0]);
        $this->assertArrayHasKey('Station 2', $chartData[0]);
        $this->assertEquals(10.0, $chartData[0]['Station 1']);
        $this->assertEquals(15.0, $chartData[0]['Station 2']);
    }
}
