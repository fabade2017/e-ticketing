<?php

namespace App\Http\Middleware;

use App\Models\BrandingLogo;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'service_number' => $request->user()->service_number,
                    'rank' => $request->user()->rank,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'systemSettings' => [
                'face_auth_mode' => SystemSetting::getValue('face_auth_mode', 'local'),
                'face_auth_enabled' => SystemSetting::getValue('face_auth_enabled', '1') === '1',
            ],
            'branding' => [
                'app_name' => SystemSetting::getValue('app_name', config('app.name', 'NA E-Ticket')),
                'app_motto' => SystemSetting::getValue('app_motto', 'Secure • Offline-Capable • Nigerian Army'),
                'app_logo' => BrandingLogo::displayName(),
                'logo_url' => BrandingLogo::url(), // full URL for <img> and favicon; null if none
            ],
        ];
    }
}
