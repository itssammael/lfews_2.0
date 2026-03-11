<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\River;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoDataUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create basic roles
        Role::create(['name' => 'admin', 'can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]);
        Role::create(['name' => 'viewer', 'can_create' => false, 'can_read' => true, 'can_update' => false, 'can_delete' => false]);
    }

    public function test_user_can_fetch_geo_data()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'viewer')->first());
        
         River::create([
            'name' => 'Test River',
            'properties' => ['test' => 'data'],
            'geometry' => ['type' => 'LineString', 'coordinates' => [[0, 0], [1, 1]]],
        ]);

        $response = $this->actingAs($user)->getJson(route('api.geo-data.fetch', ['type' => 'river']));

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(1, 'features');
    }

    public function test_user_with_create_permission_can_update_geo_data()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        
        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['seg_name' => 'New River'],
                    'geometry' => ['type' => 'LineString', 'coordinates' => [[122, 9], [123, 10]]],
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson(route('api.geo-data.update'), [
            'type' => 'river',
            'geojson' => $geoJson,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(1, River::count());
        $this->assertEquals('New River', River::first()->name);
    }
}
