<?php

namespace App\Modules\Care\Services;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CareAlertService
{
    public function metrics(User $user, bool $includeAll): array
    {
        $hospitalIds = $this->hospitalIds($user, $includeAll);

        $startOfLagosDay = Carbon::now('Africa/Lagos')->startOfDay()->utc();
        $endOfLagosDay = $startOfLagosDay->copy()->addDay();

        $pendingRegistrations = CareRegistration::pending()
            ->whereIn('hospital_id', $hospitalIds->all())
            ->count();

        $todayVisits = CareVisit::whereIn('hospital_id', $hospitalIds->all())
            ->whereBetween('created_at', [$startOfLagosDay, $endOfLagosDay])
            ->count();

        $pendingLabResults = CareLabOrder::pending()
            ->whereIn('hospital_id', $hospitalIds->all())
            ->count();

        return [
            [
                'label' => 'Pending registrations',
                'value' => $pendingRegistrations,
                'url' => '/care/registrations',
            ],
            [
                'label' => "Today's visits",
                'value' => $todayVisits,
                'url' => '/care/visits',
            ],
            [
                'label' => 'Pending lab results',
                'value' => $pendingLabResults,
                'url' => '/care/lab',
            ],
        ];
    }

    public function refresh(int $hospitalId): array
    {
        $pendingRegistrations = CareRegistration::pending()
            ->where('hospital_id', $hospitalId)
            ->count();

        $pendingLabResults = CareLabOrder::pending()
            ->where('hospital_id', $hospitalId)
            ->count();

        if ($pendingRegistrations > 0) {
            $this->notifyHospitalRoles($hospitalId, ['Reception', 'HospitalAdmin'], 'Care registrations are awaiting review', '/care/registrations', 'care.pending_registrations');
        }

        if ($pendingLabResults > 0) {
            $this->notifyHospitalRoles($hospitalId, ['Lab', 'HospitalAdmin'], 'Care lab orders are awaiting results', '/care/lab', 'care.pending_lab_results');
        }

        return [
            'pending_registrations' => $pendingRegistrations,
            'pending_lab_results' => $pendingLabResults,
        ];
    }

    private function hospitalIds(User $user, bool $includeAll): Collection
    {
        if ($includeAll && ($user->hasRole('SuperAdmin') || $user->can('view network reports'))) {
            return Hospital::active()->pluck('id');
        }

        return collect([$user->current_hospital_id])->filter();
    }

    private function notifyHospitalRoles(int $hospitalId, array $roles, string $title, string $url, string $type): void
    {
        $alreadyNotified = SuiteNotification::where('hospital_id', $hospitalId)
            ->where('type', $type)
            ->whereDate('created_at', today('Africa/Lagos'))
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        User::whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->whereHas('hospitals', function ($query) use ($hospitalId): void {
                $query->where('hospitals.id', $hospitalId)
                    ->wherePivot('is_active', true);
            })
            ->each(function (User $user) use ($hospitalId, $title, $url, $type): void {
                SuiteNotification::create([
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'module' => 'care',
                    'type' => $type,
                    'title' => $title,
                    'url' => $url,
                    'hospital_id' => $hospitalId,
                ]);
            });
    }
}
