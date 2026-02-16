<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\PagesController::class, 'index'])->name('home');
Route::get('/awards', [\App\Http\Controllers\PagesController::class, 'awards'])->name('awards');
Route::get('/services', [\App\Http\Controllers\PagesController::class, 'services'])->name('services');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/pull-water-data', [\App\Http\Controllers\DashboardController::class, 'pullWaterData'])->name('dashboard.pull-water-data')
        ->middleware('can:manage-data');
    Route::post('/dashboard/pull-weather-data', [\App\Http\Controllers\DashboardController::class, 'pullWeatherData'])->name('dashboard.pull-weather-data')
        ->middleware('can:manage-data');

    Route::get('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'index'])->name('water-level-sensors');
    Route::get('/water-level-sensors/create', [\App\Http\Controllers\WaterLevelSensorController::class, 'create'])->name('water-level-sensors.create')
        ->middleware('can:manage-data');
    Route::post('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'store'])->name('water-level-sensors.store')
        ->middleware('can:manage-data');
    Route::get('/water-level-sensors/{id}/edit', [\App\Http\Controllers\WaterLevelSensorController::class, 'edit'])->name('water-level-sensors.edit')
        ->middleware('can:manage-data');
    Route::put('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'update'])->name('water-level-sensors.update')
        ->middleware('can:manage-data');
    Route::delete('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'destroy'])->name('water-level-sensors.destroy')
        ->middleware('can:admin-only');
    Route::post('/water-level-sensors/pull-data', [\App\Http\Controllers\WaterLevelSensorController::class, 'pullWaterData'])->name('water-level-sensors.pull-data')
        ->middleware('can:manage-data');

    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/water-level-data', [\App\Http\Controllers\ReportController::class, 'getWaterLevelData'])->name('reports.water-level-data');
    Route::get('/reports/weather-observation-data', [\App\Http\Controllers\ReportController::class, 'getWeatherObservationData'])->name('reports.weather-observation-data');

    Route::get('/weather-stations', [\App\Http\Controllers\WeatherStationController::class, 'index'])->name('weather-stations');
    Route::get('/weather-stations/create', [\App\Http\Controllers\WeatherStationController::class, 'create'])->name('weather-stations.create')
        ->middleware('can:manage-data');
    Route::post('/weather-stations', [\App\Http\Controllers\WeatherStationController::class, 'store'])->name('weather-stations.store')
        ->middleware('can:manage-data');
    Route::get('/weather-stations/{id}/edit', [\App\Http\Controllers\WeatherStationController::class, 'edit'])->name('weather-stations.edit')
        ->middleware('can:manage-data');
    Route::put('/weather-stations/{weather_station}', [\App\Http\Controllers\WeatherStationController::class, 'update'])->name('weather-stations.update')
        ->middleware('can:manage-data');
    Route::delete('/weather-stations/{weather_station}', [\App\Http\Controllers\WeatherStationController::class, 'destroy'])->name('weather-stations.destroy')
        ->middleware('can:admin-only');
    Route::post('/weather-stations/pull-observation-data', [\App\Http\Controllers\WeatherStationController::class, 'pullObservationData'])->name('weather-stations.pull-observation-data')
        ->middleware('can:manage-data');

    Route::get('/locations/create', [\App\Http\Controllers\LocationController::class, 'create'])->name('locations.create')
        ->middleware('can:manage-data');
    Route::post('/locations', [\App\Http\Controllers\LocationController::class, 'store'])->name('locations.store')
        ->middleware('can:manage-data');

    Route::middleware('can:admin-only')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::delete('/rivers/{river}', [\App\Http\Controllers\RiverController::class, 'destroy'])->name('rivers.destroy');
    });

    Route::get('/rivers', [\App\Http\Controllers\RiverController::class, 'index'])->name('rivers.index');

    Route::middleware('can:manage-data')->group(function () {
        Route::resource('rivers', \App\Http\Controllers\RiverController::class)->except(['index', 'destroy']);
    });

    Route::get('/locator', [\App\Http\Controllers\LocatorController::class, 'index'])->name('locator');

    Route::get('/data-migration', [\App\Http\Controllers\DataMigrationController::class, 'index'])->name('data-migration.index')
        ->middleware('can:manage-data');
    Route::post('/data-migration/import', [\App\Http\Controllers\DataMigrationController::class, 'import'])->name('data-migration.import')
        ->middleware('can:manage-data');

    Route::post('/connectivity/ping', [\App\Http\Controllers\ConnectivityController::class, 'ping'])
        ->name('connectivity.ping')
        ->middleware('can:manage-data');
});
