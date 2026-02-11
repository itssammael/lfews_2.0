<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:pull-modbus-data')->everyFiveMinutes();
Schedule::command('app:pull-weather-observation-data')->everyFiveMinutes();
