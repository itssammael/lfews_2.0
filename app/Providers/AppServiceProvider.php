<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // URL::forceScheme('https');

        \Illuminate\Support\Facades\Gate::define('admin-only', function ($user) {
            return $user->roles->contains('name', 'admin');
        });

        \Illuminate\Support\Facades\Gate::define('can-create', function ($user) {
            return $user->roles->contains(fn($role) => (bool)$role->can_create);
        });

        \Illuminate\Support\Facades\Gate::define('can-read', function ($user) {
            return $user->roles->contains(fn($role) => (bool)$role->can_read);
        });

        \Illuminate\Support\Facades\Gate::define('can-update', function ($user) {
            return $user->roles->contains(fn($role) => (bool)$role->can_update);
        });

        \Illuminate\Support\Facades\Gate::define('can-delete', function ($user) {
            return $user->roles->contains(fn($role) => (bool)$role->can_delete);
        });

        // Bridge gate for existing logic
        \Illuminate\Support\Facades\Gate::define('manage-data', function ($user) {
            return $user->roles->contains(
            fn($role) =>
            (bool)$role->can_create || (bool)$role->can_update || (bool)$role->can_delete
            );
        });

        \Illuminate\Support\Facades\Gate::define('view-only', function ($user) {
            return \Illuminate\Support\Facades\Gate::allows('can-read');
        });
    }
}