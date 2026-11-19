<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Services\CareAlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareWorkspaceController extends Controller
{
    public function dashboard(Request $request, CareAlertService $alerts): Response
    {
        $user = auth()->user();
        $hospitalIds = $this->hospitalIds($request);
        $isAll = $this->isAll($request);

        $registrations = CareRegistration::pending()
            ->whereIn('hospital_id', $hospitalIds)
            ->with('patient:id,first_name,last_name,network_number,category')
            ->latest()
            ->limit(10)
            ->get();

        $visits = CareVisit::whereIn('hospital_id', $hospitalIds)
            ->with('patient:id,first_name,last_name,network_number')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Care/Workspace/Dashboard', [
            'insights' => $alerts->metrics($user, $isAll),
            'registrations' => $registrations,
            'visits' => $visits,
            'currentHospital' => $user->currentHospital ? [
                'id' => $user->currentHospital->id,
                'name' => $user->currentHospital->name,
                'code' => $user->currentHospital->code,
            ] : null,
            'isAll' => $isAll,
            'canSeeAll' => $user->hasRole('SuperAdmin') || $user->can('view network reports'),
        ]);
    }

    private function isAll(Request $request): bool
    {
        $user = auth()->user();

        return $user->hasRole('SuperAdmin')
            || ($request->input('scope') === 'all' && $user->can('view network reports'));
    }

    /** @return array<int, int> */
    private function hospitalIds(Request $request): array
    {
        if ($this->isAll($request)) {
            return Hospital::active()->pluck('id')->all();
        }

        return array_values(array_filter([auth()->user()->current_hospital_id]));
    }
}
