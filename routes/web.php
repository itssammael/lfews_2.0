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
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'index'])->name('water-level-sensors');
    Route::get('/water-level-sensors/create', [\App\Http\Controllers\WaterLevelSensorController::class, 'create'])->name('water-level-sensors.create');
    Route::post('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'store'])->name('water-level-sensors.store');
    Route::get('/water-level-sensors/{id}/edit', [\App\Http\Controllers\WaterLevelSensorController::class, 'edit'])->name('water-level-sensors.edit');
    Route::put('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'update'])->name('water-level-sensors.update');

    Route::get('/weather-stations', function () {
        return Inertia::render('WeatherStations');
    })->name('weather-stations');
});
