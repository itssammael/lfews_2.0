<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\PagesController::class, 'index'])->name('home');
Route::get('/awards', [\App\Http\Controllers\PagesController::class, 'awards'])->name('awards');
Route::get('/services', [\App\Http\Controllers\PagesController::class, 'services'])->name('services');
Route::get('/local-weather-map', [\App\Http\Controllers\PagesController::class, 'localWeatherMap'])->name('local-weather-map');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/pull-water-data', [\App\Http\Controllers\DashboardController::class, 'pullWaterData'])->name('dashboard.pull-water-data')
        ->middleware('can:can-create');
    Route::post('/dashboard/pull-weather-data', [\App\Http\Controllers\DashboardController::class, 'pullWeatherData'])->name('dashboard.pull-weather-data')
        ->middleware('can:can-create');
    Route::post('/dashboard/refresh-water-data', [\App\Http\Controllers\DashboardController::class, 'refreshWaterData'])->name('dashboard.refresh-water-data')
        ->middleware('can:can-read');
    Route::post('/dashboard/refresh-weather-data', [\App\Http\Controllers\DashboardController::class, 'refreshWeatherData'])->name('dashboard.refresh-weather-data')
        ->middleware('can:can-read');

    Route::get('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'index'])->name('water-level-sensors')
        ->middleware('can:can-read');
    Route::get('/water-level-sensors/create', [\App\Http\Controllers\WaterLevelSensorController::class, 'create'])->name('water-level-sensors.create')
        ->middleware('can:can-create');
    Route::post('/water-level-sensors', [\App\Http\Controllers\WaterLevelSensorController::class, 'store'])->name('water-level-sensors.store')
        ->middleware('can:can-create');
    Route::get('/water-level-sensors/{id}/edit', [\App\Http\Controllers\WaterLevelSensorController::class, 'edit'])->name('water-level-sensors.edit')
        ->middleware('can:can-update');
    Route::put('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'update'])->name('water-level-sensors.update')
        ->middleware('can:can-update');
    Route::delete('/water-level-sensors/{water_level_sensor}', [\App\Http\Controllers\WaterLevelSensorController::class, 'destroy'])->name('water-level-sensors.destroy')
        ->middleware('can:can-delete');
    Route::post('/water-level-sensors/pull-data', [\App\Http\Controllers\WaterLevelSensorController::class, 'pullWaterData'])->name('water-level-sensors.pull-data')
        ->middleware('can:can-create');

    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports')
        ->middleware('can:can-read');
    Route::get('/reports/water-level-data', [\App\Http\Controllers\ReportController::class, 'getWaterLevelData'])->name('reports.water-level-data')
        ->middleware('can:can-read');
    Route::get('/reports/weather-observation-data', [\App\Http\Controllers\ReportController::class, 'getWeatherObservationData'])->name('reports.weather-observation-data')
        ->middleware('can:can-read');

    Route::get('/weather-stations', [\App\Http\Controllers\WeatherStationController::class, 'index'])->name('weather-stations')
        ->middleware('can:can-read');
    Route::get('/weather-stations/create', [\App\Http\Controllers\WeatherStationController::class, 'create'])->name('weather-stations.create')
        ->middleware('can:can-create');
    Route::post('/weather-stations', [\App\Http\Controllers\WeatherStationController::class, 'store'])->name('weather-stations.store')
        ->middleware('can:can-create');
    Route::get('/weather-stations/{id}/edit', [\App\Http\Controllers\WeatherStationController::class, 'edit'])->name('weather-stations.edit')
        ->middleware('can:can-update');
    Route::put('/weather-stations/{weather_station}', [\App\Http\Controllers\WeatherStationController::class, 'update'])->name('weather-stations.update')
        ->middleware('can:can-update');
    Route::delete('/weather-stations/{weather_station}', [\App\Http\Controllers\WeatherStationController::class, 'destroy'])->name('weather-stations.destroy')
        ->middleware('can:can-delete');
    Route::post('/weather-stations/pull-observation-data', [\App\Http\Controllers\WeatherStationController::class, 'pullObservationData'])->name('weather-stations.pull-observation-data')
        ->middleware('can:can-create');

    Route::get('/locations/create', [\App\Http\Controllers\LocationController::class, 'create'])->name('locations.create')
        ->middleware('can:can-create');
    Route::post('/locations', [\App\Http\Controllers\LocationController::class, 'store'])->name('locations.store')
        ->middleware('can:can-create');

    Route::middleware('can:admin-only')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('roles', \App\Http\Controllers\RoleController::class)->only(['store', 'update', 'destroy']);
        Route::delete('/rivers/{river}', [\App\Http\Controllers\RiverController::class, 'destroy'])->name('can-delete');
    });

    Route::get('/rivers', [\App\Http\Controllers\RiverController::class, 'index'])->name('rivers.index')
        ->middleware('can:can-read');

    Route::middleware('can:can-read')->group(function () {
        Route::resource('rivers', \App\Http\Controllers\RiverController::class)->except(['index', 'destroy']);
    });

    Route::get('/locator', [\App\Http\Controllers\LocatorController::class, 'index'])->name('locator')
        ->middleware('can:can-read');
    Route::get('/locator/api/contours', [\App\Http\Controllers\LocatorController::class, 'apiContours'])->name('locator.api.contours')
        ->middleware('can:can-read');
    Route::get('/locator/api/sitios', [\App\Http\Controllers\LocatorController::class, 'apiSitios'])->name('locator.api.sitios')
        ->middleware('can:can-read');

    Route::get('/hazard-map', [\App\Http\Controllers\HazardMapController::class, 'index'])->name('hazard-map.index')
        ->middleware('can:can-read');

    Route::middleware('can:can-read')->group(function () {
        Route::resource('hazard-map', \App\Http\Controllers\HazardMapController::class)->except(['index']);
    });

    Route::get('/data-migration', [\App\Http\Controllers\DataMigrationController::class, 'index'])->name('data-migration.index')
        ->middleware('can:can-create');
    Route::post('/data-migration/import', [\App\Http\Controllers\DataMigrationController::class, 'import'])->name('data-migration.import')
        ->middleware('can:can-create');

    Route::post('/connectivity/ping', [\App\Http\Controllers\ConnectivityController::class, 'ping'])
        ->name('connectivity.ping')
        ->middleware('can:can-read');

    Route::get('/evacuation-center', [\App\Http\Controllers\EvacuationCenterController::class, 'index'])
        ->name('evacuation-center.index')
        ->middleware('can:can-read');

    Route::get('/evacuation-center/api', [\App\Http\Controllers\EvacuationCenterController::class, 'getEvacuationCenters'])
        ->name('evacuation-center.api')
        ->middleware('can:can-read');

    Route::get('/flood-hazard-map', [\App\Http\Controllers\FloodRiskController::class, 'index'])->name('flood_risks.index')
        ->middleware('can:can-read');

    Route::middleware('can:can-read')->group(function () {
        Route::resource('flood_risks', \App\Http\Controllers\FloodRiskController::class)->except(['index']);
    });

    Route::get('/barangays-sitios', [\App\Http\Controllers\BarangaySitioController::class, 'index'])->name('barangays-sitios.index')
        ->middleware('can:can-read');
    Route::get('/barangays-sitios/api/geojson', [\App\Http\Controllers\BarangaySitioController::class, 'getGeoJson'])->name('barangays-sitios.api.geojson')
        ->middleware('can:can-read');

    Route::get('/lunar-tides', [\App\Http\Controllers\TideController::class, 'index'])->name('lunar-tides')
        ->middleware('can:can-read');
    Route::post('/lunar-tides/sync', [\App\Http\Controllers\TideController::class, 'syncTides'])->name('lunar-tides.sync')
        ->middleware('can:can-create');

    Route::get('/system-settings', [\App\Http\Controllers\PagesController::class, 'systemSettings'])
        ->name('system-settings')
        ->middleware('auth:sanctum');

    Route::post('/system-settings', [\App\Http\Controllers\PagesController::class, 'updateSystemSettings'])
        ->name('system-settings.update')
        ->middleware('auth:sanctum');

    Route::get('/update-geo-data', [\App\Http\Controllers\PagesController::class, 'updateGeoData'])
        ->name('update-geo-data')
        ->middleware('can:can-read');

    Route::get('/api/geo-data/{type}', [\App\Http\Controllers\GeoDataController::class, 'fetchCurrentData'])
        ->name('api.geo-data.fetch')
        ->middleware('can:can-read');

    Route::post('/api/geo-data/update', [\App\Http\Controllers\GeoDataController::class, 'update'])
        ->name('api.geo-data.update')
        ->middleware('can:can-create');
});
