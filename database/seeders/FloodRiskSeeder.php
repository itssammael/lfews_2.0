<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FloodRiskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('resources/js/Geojson/BayawanFloodHazard.json');
        
        if (!file_exists($path)) {
            $this->command->error("File not found: {$path}");
            return;
        }

        ini_set('memory_limit', '1024M');

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (!isset($data['features'])) {
            $this->command->error("Invalid GeoJSON format.");
            return;
        }

        $this->command->info("Seeding flood risks...");
        $bar = $this->command->getOutput()->createProgressBar(count($data['features']));
        $bar->start();

        foreach (collect($data['features'])->chunk(100) as $chunk) {
            $floodRisks = [];
            foreach ($chunk as $feature) {
                // Determine a name if possible, otherwise use a default
                $name = $feature['properties']['FS_VH'] ?? 'Flood Hazard Area';
                
                $floodRisks[] = [
                    'name' => $name,
                    'properties' => json_encode($feature['properties'] ?? []),
                    'geometry' => json_encode($feature['geometry']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $bar->advance();
            }
            \App\Models\FloodRisk::insert($floodRisks);
        }

        $bar->finish();
        $this->command->info("\nFlood risks seeded successfully.");
    }
}
