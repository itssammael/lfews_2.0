<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/pull-data', [\App\Http\Controllers\DashboardController::class, 'pullWaterData'])->name('dashboard.pull-data');

    Route::get('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'index'])->name('water-level-sensors');
    Route::get('/water-level-sensors/create', [\App\Http\Controllers\WaterLevelSensorController::class, 'create'])->name('water-level-sensors.create');
    Route::post('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'store'])->name('water-level-sensors.store');
    Route::get('/water-level-sensors/{id}/edit', [\App\Http\Controllers\WaterLevelSensorController::class, 'edit'])->name('water-level-sensors.edit');
    Route::put('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'update'])->name('water-level-sensors.update');
    Route::delete('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'destroy'])->name('water-level-sensors.destroy');

    Route::get('/weather-stations', [\App\Http\Controllers\WeatherStationController::class, 'index'])->name('weather-stations');
    Route::get('/weather-stations/create', [\App\Http\Controllers\WeatherStationController::class, 'create'])->name('weather-stations.create');
    Route::post('/weather-stations', [\App\Http\Controllers\WeatherStationController::class, 'store'])->name('weather-stations.store');
    Route::get('/weather-stations/{id}/edit', [\App\Http\Controllers\WeatherStationController::class, 'edit'])->name('weather-stations.edit');
    Route::put('/weather-stations/{weather_station}', [\App\Http\Controllers\WeatherStationController::class, 'update'])->name('weather-stations.update');
    Route::delete('/weather-stations/{weather_station}', [\App\Http\Controllers\WeatherStationController::class, 'destroy'])->name('weather-stations.destroy');

    Route::get('/locations/create', [\App\Http\Controllers\LocationController::class, 'create'])->name('locations.create');
    Route::post('/locations', [\App\Http\Controllers\LocationController::class, 'store'])->name('locations.store');
});
