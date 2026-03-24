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
                    'description' => 'Timeouts for data pull operations (in seconds).',
                ],
                'description' => 'Controls the frequency and timeout of data pulling operations from Water Level Sensor and Weather Station.',
            ]
        );

        SystemSetting::updateOrCreate(
            ['name' => 'heat_index_advisory_gauge'],
            [
                'value' => [
                    [ 'color' => '#33cc33', 'advice' => "Heat Index within bearable parameters.", 'label' => "Normal", 'temprange' => '< 27°C' ],
                    [ 'color' => '#ffcc00', 'advice' => "HEAT ALERT. Fatigue is possible with prolonged exposure and activity. Continuing activity could result in heat cramps.", 'label' => "Caution", 'temprange' => '28°C - 32°C' ],
                    [ 'color' => '#ff9900', 'advice' => "HEAT ALERT. Heat cramps and heat exhaustion are possible. Continuing activity could result in heatstroke.", 'label' => "Ext. Caution", 'temprange' => '33°C - 41°C' ],
                    [ 'color' => '#cc0000', 'advice' => "EXTREME HEAT ALERT. Heat cramps and heat exhaustion are likely. Heatstroke is probable with continued exposure.", 'label' => "Danger", 'temprange' => '42°C - 51°C' ],
                    [ 'color' => '#990000', 'advice' => "EXTREME HEAT ALERT. Heatstroke is highly likely with continued exposure.", 'label' => "Extreme Danger", 'temprange' => '>= 52°C' ]
                ],
                'description' => 'Configuration for Heat Index Advisory Gauge (labels, colors, advice, and ranges).',
            ]
        );

        SystemSetting::updateOrCreate(
            ['name' => 'api_key'],
            [
                'value' => [
                    'name' => 'API/wunderground',
                    'key' => 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d',
                ],
                'description' => 'API Key configuration for Weather Station Setup.',
            ]
        );
    }
}
