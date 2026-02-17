<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::updateOrCreate(
            ['name' => 'data_pull_timeout'],
            [
                'value' => [
                    'water_level_sensor' => 300,
                    'weather_station' => 300,
                ]
            ]
        );
    }
}
