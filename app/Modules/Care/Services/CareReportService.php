<?php

namespace App\Modules\Care\Services;

use App\Models\User;
use App\Modules\Care\Models\CareDiagnosis;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CareMonthlyReport;
use App\Modules\Care\Models\CareVisit;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class CareReportService
{
    public function generate(int $month, int $year, ?int $hospitalId, User $actor): CareMonthlyReport
    {
        if (! $actor->can('view reports')) {
            throw new AuthorizationException('You cannot view care reports.');
        }

        if ($hospitalId === null && ! $actor->can('view network reports')) {
            throw new AuthorizationException('You cannot view network-wide care reports.');
        }

        if ($hospitalId !== null && ! $actor->canAccessHospital($hospitalId)) {
            throw new AuthorizationException('You cannot report on this hospital.');
        }

        $monthKey = sprintf('%04d-%02d-01', $year, $month);
        $start = Carbon::create($year, $month, 1, 0, 0, 0, 'Africa/Lagos')->utc();
        $end = $start->copy()->addMonth();

        $visits = CareVisit::completed()
            ->whereBetween('completed_at', [$start, $end])
            ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
            ->get(['patient_id']);

        $labOrders = CareLabOrder::completed()
            ->whereBetween('completed_at', [$start, $end])
            ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
            ->get(['patient_id']);

        $diseaseEncounters = CareDiagnosis::active()
            ->whereBetween('created_at', [$start, $end])
            ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
            ->count();

        $scope = $hospitalId === null ? 'network' : 'hospital';
        $scopeKey = $hospitalId === null
            ? "network:{$monthKey}"
            : "hospital:{$hospitalId}:{$monthKey}";

        return CareMonthlyReport::updateOrCreate([
            'scope_key' => $scopeKey,
        ], [
            'hospital_id' => $hospitalId,
            'scope' => $scope,
            'report_month' => $monthKey,
            'distinct_patients' => $visits->pluck('patient_id')->unique()->count(),
            'completed_visits' => $visits->count(),
            'patients_tested' => $labOrders->pluck('patient_id')->unique()->count(),
            'tests_performed' => $labOrders->count(),
            'disease_encounters' => $diseaseEncounters,
            'generated_by' => $actor->id,
            'generated_at' => now(),
            'metadata' => [
                'timezone' => 'Africa/Lagos',
                'range_start' => $start->toIso8601String(),
                'range_end' => $end->toIso8601String(),
            ],
        ]);
    }

    public function csv(CareMonthlyReport $report): string
    {
        $rows = [
            ['metric', 'value'],
            ['month', $report->report_month->format('Y-m')],
            ['scope', $report->scope],
            ['distinct_patients', $report->distinct_patients],
            ['completed_visits', $report->completed_visits],
            ['patients_tested', $report->patients_tested],
            ['tests_performed', $report->tests_performed],
            ['disease_encounters', $report->disease_encounters],
        ];

        return collect($rows)
            ->map(fn (array $row): string => implode(',', array_map(
                fn ($value): string => '"'.str_replace('"', '""', (string) $value).'"',
                $row,
            )))
            ->implode("\n");
    }
}
