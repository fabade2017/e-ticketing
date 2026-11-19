<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareHospitalController extends Controller
{
    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
        ]);

        $hospitalId = (int) $validated['hospital_id'];
        $user = $request->user();

        if (! $user->canAccessHospital($hospitalId)) {
            abort(403, 'You cannot select this hospital.');
        }

        $user->update(['current_hospital_id' => $hospitalId]);

        return back()->with('success', 'Hospital selected.');
    }
}
