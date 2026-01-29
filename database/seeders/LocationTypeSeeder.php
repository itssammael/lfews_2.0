<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['description' => 'River', 'has_multiple_dots' => true],
            ['description' => 'Device/Sensor', 'has_multiple_dots' => false],
            ['description' => 'Device/Station', 'has_multiple_dots' => false],
            ['description' => 'Evacuation Centers', 'has_multiple_dots' => false],
        ];

        foreach ($types as $type) {
            \App\Models\LocationType::create($type);
        }
    }
}
