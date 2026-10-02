<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BrandingController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $logoPath = SystemSetting::getValue('app_logo', null);
        $logoUrl = null;
        if ($logoPath) {
            try {
                $logoUrl = Storage::disk('public')->exists($logoPath) ? Storage::disk('public')->url($logoPath) : null;
                if (!$logoUrl) $logoPath = null; // file missing (deploy wipe) — fallback to none
            } catch (\Throwable $e) { $logoPath = null; }
        }
        return Inertia::render('Admin/Branding', [
            'branding' => [
                'app_name' => SystemSetting::getValue('app_name', 'NA E-Ticket'),
                'app_motto' => SystemSetting::getValue('app_motto', 'Secure • Offline-Capable • Nigerian Army'),
                'app_logo' => $logoPath,
                'logo_url' => $logoUrl,
            ],
        ]);
    }

    public function update(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate([
            'app_name' => 'required|string|max:100',
            'app_motto' => 'required|string|max:200',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'remove_logo' => 'boolean',
        ]);

        SystemSetting::setValue('app_name', $request->app_name);
        SystemSetting::setValue('app_motto', $request->app_motto);

        if ($request->boolean('remove_logo')) {
            $old = SystemSetting::getValue('app_logo');
            if ($old) Storage::disk('public')->delete($old);
            SystemSetting::setValue('app_logo', '');
        } elseif ($request->hasFile('logo')) {
            $old = SystemSetting::getValue('app_logo');
            if ($old) Storage::disk('public')->delete($old);
            // ensure branding dir exists and storage link is present
            try { Storage::disk('public')->makeDirectory('branding'); } catch (\Throwable $e) {}
            $path = $request->file('logo')->store('branding', 'public');
            SystemSetting::setValue('app_logo', $path);
        }

        // ensure storage symlink exists (Render/Docker ephemeral)
        try { if (!is_link(public_path('storage')) && !is_dir(public_path('storage'))) \Illuminate\Support\Facades\Artisan::call('storage:link'); } catch (\Throwable $e) {}

        return redirect()->route('admin.branding')->with('success', 'Branding updated — logo, name and motto will reflect instantly. If logo 404 after deploy, re-upload (Render local disk is ephemeral — consider S3 for persistence).');
    }
}
