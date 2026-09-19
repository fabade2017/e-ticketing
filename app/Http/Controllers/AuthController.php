<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->merge([
            'email' => $request->email ?: null,
            'service_number' => $request->service_number ?: null,
        ]);
        $request->validate([
            'service_number' => 'required_without:email|nullable|string',
            'email' => 'required_without:service_number|nullable|email',
            'password' => 'required|string',
        ]);

        $field = $request->email ? 'email' : 'service_number';
        $credentials = [$field => $request->$field, 'password' => $request->password, 'is_active' => true];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }
        return back()->withErrors(['service_number' => 'Invalid credentials or inactive account']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
