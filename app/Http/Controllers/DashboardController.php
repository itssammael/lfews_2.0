<?php

namespace App\Http\Controllers;

use App\Services\ModbusService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
      
        return Inertia::render('Dashboard', [
            'sensors' => \App\Models\WaterLevelSensor::all(),
            'stations' => \App\Models\WeatherStation::all(),
            'latestData' => \Illuminate\Support\Facades\Cache::get('latest_modbus_data'),
            'historyData' => \Illuminate\Support\Facades\Cache::get('modbus_history', []),
            'latestWeatherData' => \Illuminate\Support\Facades\Cache::get('latest_wunderground_api_data'),
            'historyWeatherData' => \Illuminate\Support\Facades\Cache::get('wunderground_api_history', []),
        ]);
    }

    public function pullWeatherData()
    {
        $ip = "192.168.81.59";
        $port = 22222;
        $fp = fsockopen($ip, $port, $errno, $errstr, 2);

       if ($fp) {
    // 1. Wake up the console
    fwrite($fp, "\n");
    usleep(500000); // Wait 0.5s
    $response = fread($fp, 1024);

    // 2. Request 1 LOOP packet
    fwrite($fp, "LOOP 1\n");
    usleep(500000);
    
    // 3. Read binary data (1 byte ACK + 99 bytes packet)
    // Davis can be slow; ensure we get all 100 bytes
    $binaryData = "";
    $startTime = microtime(true);
    while (strlen($binaryData) < 100 && (microtime(true) - $startTime) < 2) {
        $chunk = fread($fp, 100 - strlen($binaryData));
        if ($chunk === false || $chunk === "") break;
        $binaryData .= $chunk;
    }
    
    // 4. Verify ACK (Decimal 6) and we have a full packet
    if (strlen($binaryData) === 100 && ord($binaryData[0]) === 6) {
        $packet = substr($binaryData, 1);
        
        // Individual bytes/offsets based on Davis LOOP 1 Packet Spec
        $barometer      = unpack("v", substr($packet, 7, 2))[1];
        $temp_out       = unpack("v", substr($packet, 12, 2))[1];
        $wind_speed     = ord($packet[14]);
        $wind_direction = unpack("v", substr($packet, 16, 2))[1];
        $humidity       = ord($packet[33]);
        $rain_rate      = unpack("v", substr($packet, 45, 2))[1];
        $rain_total     = unpack("v", substr($packet, 54, 2))[1];
        $wind_gust      = unpack("v", substr($packet, 64, 2))[1];
        $solar          = unpack("v", substr($packet, 67, 2))[1];
        $uv             = ord($packet[70]);
        
        // Scaling and "No Data" handling
        // Davis sentinel values: 32767 or 65535 for words, 255 for bytes
        $weather = [
            'temp'               => ($temp_out == 32767) ? null : $temp_out / 10,
            'humidity'           => ($humidity == 255) ? null : $humidity,
            'wind_speed'         => ($wind_speed == 255) ? null : $wind_speed,
            'wind_direction'     => ($wind_direction == 32767 || $wind_direction == 0) ? null : $wind_direction,
            'wind_gust'          => ($wind_gust == 32767 || $wind_gust == 65535) ? null : $wind_gust,
            'pressure'           => ($barometer == 0) ? null : $barometer / 1000,
            'precipitation_rate' => ($rain_rate == 65535) ? 0 : $rain_rate * 0.01,
            'precipitation_total'=> ($rain_total == 65535) ? 0 : $rain_total * 0.01,
            'solar_radiation'    => ($solar == 32767) ? null : $solar,
            'uv'                 => ($uv == 255) ? null : $uv / 10,
        ];
   
        dd($solar);
    }
    
    fclose($fp);
}
        $weatherStationController = new WeatherStationController();
        $results = $weatherStationController->pullObservationData();

        return redirect()->route('dashboard')->with('weatherResult', $results);
    }

    public function pullWaterData(ModbusService $modbusService)
    {
        $waterLevelSensorController = new WaterLevelSensorController();
        $results = $waterLevelSensorController->pullWaterData($modbusService);

        return redirect()->route('dashboard')->with('modbusResult', $results);
    }
}
