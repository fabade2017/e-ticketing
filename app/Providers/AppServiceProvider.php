<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SuperAdmin automatically holds every permission (current and future).
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('SuperAdmin')) {
                return true;
            }

            return null;
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
