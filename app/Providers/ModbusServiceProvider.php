<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModbusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\ModbusService::class, function ($app) {
            return new \App\Services\ModbusService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
