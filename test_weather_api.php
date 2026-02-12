<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

// Bootstrap Laravel's Http facade
$app = new Container();
$app->singleton('config', function () {
    return new \Illuminate\Config\Repository();
});
$app->singleton('events', function () {
    return new \Illuminate\Events\Dispatcher();
});
Facade::setFacadeApplication($app);

// Mocking Http component for standalone use
$factory = new \Illuminate\Http\Client\Factory();
$app->instance('Illuminate\Http\Client\Factory', $factory);

try {
    echo "Testing connectivity to api.weather.com with IPv4 forced...\n";
    $response = $factory->timeout(30)
        ->connectTimeout(15)
        ->withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ])
        ->retry(3, 2000)
        ->get('https://api.weather.com/v2/pws/observations/current', [
            'stationId' => 'IBAYAW1',
            'format' => 'json',
            'units' => 'm',
            'numericPrecision' => 'decimal',
            'apiKey' => 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d',
        ]);

    if ($response->successful()) {
        echo "SUCCESS: Connected and received data.\n";
        print_r($response->json());
    } else {
        echo "FAILED: Status " . $response->status() . "\n";
        echo $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
