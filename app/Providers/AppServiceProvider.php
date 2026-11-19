<?php

namespace App\Providers;

use App\Modules\Care\Models\CareAppointment;
use App\Modules\Care\Models\CareDutyCertificate;
use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Policies\CarePolicy;
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
        Gate::policy(CarePatient::class, CarePolicy::class);
        Gate::policy(CareRegistration::class, CarePolicy::class);
        Gate::policy(CareAppointment::class, CarePolicy::class);
        Gate::policy(CareVisit::class, CarePolicy::class);
        Gate::policy(CareLabResult::class, CarePolicy::class);
        Gate::policy(CareDutyCertificate::class, CarePolicy::class);
        Gate::policy(CareInvoice::class, CarePolicy::class);

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
