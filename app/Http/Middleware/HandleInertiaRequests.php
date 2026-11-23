<?php

namespace App\Http\Middleware;

use App\Models\BrandingLogo;
use App\Models\SystemSetting;
use App\Shared\Navigation\SuiteNavigation;
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
        $settings = SystemSetting::values([
            'face_auth_mode',
            'face_auth_enabled',
            'app_name',
            'app_motto',
            'app_download_url',
        ], [
            'face_auth_mode' => 'local',
            'face_auth_enabled' => '1',
            'app_name' => config('branding.app_name', 'NA E-Ticket'),
            'app_motto' => config('branding.app_motto', 'Secure • Offline-Capable • Nigerian Army'),
        ]);

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
                'face_auth_mode' => $settings['face_auth_mode'],
                'face_auth_enabled' => $settings['face_auth_enabled'] === '1',
            ],
            'options' => fn () => SystemSetting::optionLists(),
            'branding' => [
                'app_name' => $settings['app_name'],
                'app_motto' => $settings['app_motto'],
                'app_logo' => BrandingLogo::displayName(),
                'logo_url' => BrandingLogo::url(), // full URL for <img> and favicon; null if none
                'app_download_url' => $settings['app_download_url'] ?? config('branding.download_url'),
            ],
            'suite' => [
                'modules' => fn () => (new SuiteNavigation)->modulesFor($request->user()),
                'rooms' => fn () => (new SuiteNavigation)->roomsFor($request->user()),
                'current_hospital' => fn () => $request->user()?->currentHospital ? [
                    'id' => $request->user()->currentHospital->id,
                    'name' => $request->user()->currentHospital->name,
                    'code' => $request->user()->currentHospital->code,
                ] : null,
                'unread_notifications' => fn () => $request->user()?->suiteNotifications()->unread()->count() ?? 0,
            ],
        ];
    }
}
