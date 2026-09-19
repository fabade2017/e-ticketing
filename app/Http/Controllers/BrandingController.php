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
        return Inertia::render('Admin/Branding', [
            'branding' => [
                'app_name' => SystemSetting::getValue('app_name', 'NA E-Ticket'),
                'app_motto' => SystemSetting::getValue('app_motto', 'Secure • Offline-Capable • Nigerian Army'),
                'app_logo' => SystemSetting::getValue('app_logo', null),
                'logo_url' => SystemSetting::getValue('app_logo') ? Storage::disk('public')->url(SystemSetting::getValue('app_logo')) : null,
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
            $path = $request->file('logo')->store('branding', 'public');
            SystemSetting::setValue('app_logo', $path);
        }

        return back()->with('success', 'Branding updated — logo, name and motto will reflect instantly.');
    }
}
