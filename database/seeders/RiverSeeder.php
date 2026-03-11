<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\River;

class RiverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        River::truncate();
        $jsonPath = resource_path('js/Geojson/BayawanRivers.json'); // Adjust path
        
        if (!File::exists($jsonPath)) {
            $this->command->error("File not found: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['features'])) {
            $this->command->error("Invalid JSON format");
            return;
        }

        foreach ($data['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $name = $properties['seg_name'] ?? 'Unknown River';
            $geometry = $feature['geometry'] ?? null;

            if ($geometry) {
                River::create([
                    'name' => $name,
                    'properties' => $properties,
                    'geometry' => $geometry,
                ]);
            }
        }
        
        $this->command->info("Rivers seeded successfully.");
    }
}
