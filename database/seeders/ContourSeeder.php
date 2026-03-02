<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('resources/js/Geojson/BayawanContour.json');

        if (!file_exists($path)) {
            $this->command->error("File not found: {$path}");
            return;
        }

        ini_set('memory_limit', '2048M'); // Increase memory limit for large file

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        unset($json); // Free up memory

        if (!isset($data['features'])) {
            $this->command->error("Invalid GeoJSON format.");
            return;
        }

        $this->command->info("Seeding contours (Optimized)...");
        $bar = $this->command->getOutput()->createProgressBar(count($data['features']));
        $bar->start();

        $chunks = array_chunk($data['features'], 50);
        unset($data); // Free the large features array

        foreach ($chunks as $chunk) {
            $contours = [];
            foreach ($chunk as $feature) {
                $contours[] = [
                    'name' => $feature['properties']['name'] ?? 'Contour Line',
                    'properties' => json_encode($feature['properties'] ?? []),
                    'geometry' => json_encode($feature['geometry']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $bar->advance();
            }

            \Illuminate\Support\Facades\DB::table('contours')->insert($contours);

            unset($contours);
            gc_collect_cycles(); // Force garbage collection
        }

        $bar->finish();
        $this->command->info("\nContours seeded successfully.");
    }
}
