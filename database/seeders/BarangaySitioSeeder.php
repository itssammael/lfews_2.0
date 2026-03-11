<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Barangay;
use App\Models\Sitio;

class BarangaySitioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Barangays
        $this->seedBarangays();

        // Seed Sitios
        $this->seedSitios();
    }

    private function seedBarangays()
    {
        Barangay::truncate();
        $jsonPath = resource_path('js/Geojson/BayawanBarangay.geojson');
        
        if (!File::exists($jsonPath)) {
            $this->command->error("Barangay file not found: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['features'])) {
            $this->command->error("Invalid Barangay JSON format");
            return;
        }

        foreach ($data['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $name = $properties['barangay'] ?? 'Unknown Barangay';
            $geometry = $feature['geometry'] ?? null;

            if ($geometry) {
                Barangay::create([
                    'name' => $name,
                    'properties' => $properties,
                    'geometry' => $geometry,
                ]);
            }
        }
        
        $this->command->info("Barangays seeded successfully.");
    }

    private function seedSitios()
    {
        Sitio::truncate();
        $jsonPath = resource_path('js/Geojson/BayawanSitio.geojson');
        
        if (!File::exists($jsonPath)) {
            $this->command->error("Sitio file not found: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['features'])) {
            $this->command->error("Invalid Sitio JSON format");
            return;
        }

        foreach ($data['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $name = $properties['sitioname'] ?? 'Unknown Sitio';
            $barangayName = $properties['barangayname'] ?? null;
            $barangayId = $properties['brgy_id'] ?? null;
            $geometry = $feature['geometry'] ?? null;

            if ($geometry) {
                Sitio::create([
                    'name' => $name,
                    'barangay_name' => $barangayName,
                    'barangay_id' => $barangayId,
                    'properties' => $properties,
                    'geometry' => $geometry,
                ]);
            }
        }
        
        $this->command->info("Sitios seeded successfully.");
    }
}
