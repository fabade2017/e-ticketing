<?php

namespace App\Http\Middleware;

use App\Shared\Identity\HospitalAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHospitalSelected
{
    public function __construct(public HospitalAccess $hospitalAccess) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->hasRole('SuperAdmin')) {
            return $next($request);
        }

        $hospitalId = $user->current_hospital_id;

        if ($hospitalId !== null && $this->hospitalAccess->canAccess($user, $hospitalId)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Select an assigned hospital before continuing.'], 409);
        }

        return redirect()->route('dashboard')->with('error', 'Select an assigned hospital before continuing.');
    }
}
