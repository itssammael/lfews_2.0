<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::define('admin-only', function ($user) {
            return $user->roles->contains('name', 'admin');
        });

        \Illuminate\Support\Facades\Gate::define('manage-data', function ($user) {
            return !$user->roles->contains('name', 'user');
        });

        \Illuminate\Support\Facades\Gate::define('view-only', function ($user) {
            return true; // All authenticated users can view
        });
    }
}
