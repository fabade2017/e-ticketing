<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Models\CareAppointment;
use App\Modules\Care\Services\CareAppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareAppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $hospitalId = $user->hasRole('SuperAdmin') || ($request->input('scope') === 'all' && $user->can('view network reports'))
            ? null
            : $user->current_hospital_id;

        return Inertia::render('Care/Workspace/Appointments', [
            'appointments' => CareAppointment::with('patient:id,first_name,last_name,network_number')
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest('requested_for')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status', 'scope']),
        ]);
    }

    public function confirm(CareAppointment $appointment, CareAppointmentService $appointments): RedirectResponse
    {
        Gate::authorize('confirmAppointment', $appointment);
        $appointments->confirm($appointment, auth()->user());

        return back()->with('success', 'Appointment confirmed.');
    }
}
