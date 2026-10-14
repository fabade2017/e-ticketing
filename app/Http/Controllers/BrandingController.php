<?php

namespace App\Http\Controllers;

use App\Models\BrandingLogo;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BrandingController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasRole('SuperAdmin')) {
            abort(403);
        }

        return Inertia::render('Admin/Branding', [
            'branding' => [
                'app_name' => SystemSetting::getValue('app_name', 'NA E-Ticket'),
                'app_motto' => SystemSetting::getValue('app_motto', 'Secure • Offline-Capable • Nigerian Army'),
                'app_logo' => BrandingLogo::displayName(),
                'logo_url' => BrandingLogo::url(),
            ],
        ]);
    }

    /**
     * Public endpoint that streams the stored logo. Serving from the database
     * means the logo survives redeploys on ephemeral filesystems (Render).
     */
    public function logo()
    {
        $logo = BrandingLogo::current();
        if (! $logo || $logo->contents() === '') {
            abort(404);
        }

        $response = response($logo->contents(), 200, [
            'Content-Type' => $logo->mime ?: 'image/png',
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Prevent inline scripts if an SVG is opened directly.
        $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");

        return $response;
    }

    public function update(Request $request)
    {
        if (! auth()->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
        $request->validate([
            'app_name' => 'required|string|max:100',
            'app_motto' => 'required|string|max:200',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'remove_logo' => 'boolean',
        ]);

        SystemSetting::setValue('app_name', $request->app_name);
        SystemSetting::setValue('app_motto', $request->app_motto);

        if ($request->boolean('remove_logo')) {
            $this->deleteLegacyFile();
            BrandingLogo::forget();
            SystemSetting::setValue('app_logo', '');
        } elseif ($request->hasFile('logo')) {
            $this->deleteLegacyFile();
            try {
                BrandingLogo::store($request->file('logo'));
            } catch (\Throwable $e) {
                report($e);

                return back()->with('error', 'Could not store the logo — the branding table is missing (pending migration). Please redeploy or run the migrations.');
            }
            SystemSetting::setValue('app_logo', 'branding/logo');
        }

        return redirect()->route('admin.branding')->with('success', 'Branding updated — logo, name and motto now reflect instantly and the logo persists across deploys.');
    }

    private function deleteLegacyFile(): void
    {
        $old = SystemSetting::getValue('app_logo');
        if ($old && $old !== 'branding/logo') {
            try {
                Storage::disk('public')->delete($old);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
}
