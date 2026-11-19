<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Models\CareMonthlyReport;
use App\Modules\Care\Services\CareReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $hospitalId = $user->hasRole('SuperAdmin') || ($request->input('scope') === 'all' && $user->can('view network reports'))
            ? null
            : $user->current_hospital_id;

        return Inertia::render('Care/Workspace/Reports', [
            'reports' => CareMonthlyReport::with('hospital:id,name,code')
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->latest('generated_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['scope']),
        ]);
    }

    public function generate(Request $request, CareReportService $reports): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
        ]);

        Gate::authorize('viewReport', $validated['hospital_id'] ? (int) $validated['hospital_id'] : null);
        $reports->generate(
            (int) $validated['month'],
            (int) $validated['year'],
            $validated['hospital_id'] ? (int) $validated['hospital_id'] : null,
            auth()->user(),
        );

        return back()->with('success', 'Monthly care report generated.');
    }

    public function download(CareMonthlyReport $report, CareReportService $reports): HttpResponse
    {
        Gate::authorize('viewReport', $report->hospital_id);

        return response($reports->csv($report), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="care-report-'.$report->id.'.csv"',
        ]);
    }
}
