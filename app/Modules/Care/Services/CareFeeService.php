<?php

namespace App\Modules\Care\Services;

use App\Modules\Care\Models\CarePatient;
use Carbon\Carbon;

class CareFeeService
{
    public const MILITARY_FEE = 5000;

    public const CIVILIAN_FEE = 10000;

    public const DEPENDANT_FEE = 2500;

    public function quote(CarePatient $patient, array $attributes = []): array
    {
        $category = (string) ($attributes['category'] ?? $patient->category);
        $feeAmount = $this->baseFee($category);
        $relationship = (string) ($attributes['relationship'] ?? 'sponsor');
        $requiresSponsor = (bool) ($attributes['requires_sponsor'] ?? false);

        $checksPassed = (bool) ($attributes['identity_verified'] ?? false)
            && (bool) ($attributes['evidence_verified'] ?? false);

        if ($requiresSponsor) {
            $checksPassed = $checksPassed && (bool) ($attributes['sponsor_approved'] ?? false);
        }

        $waiverReason = null;
        $waiverAmount = 0;

        if ($checksPassed && $category === 'military') {
            $waiverAmount = $feeAmount;
            $waiverReason = 'Approved military personnel';
        } elseif ($checksPassed && $category === 'dependant' && $relationship === 'spouse') {
            $waiverAmount = $feeAmount;
            $waiverReason = 'Verified military spouse';
        } elseif ($checksPassed && $category === 'dependant' && $relationship === 'child' && $this->age($patient) < 18) {
            $waiverAmount = $feeAmount;
            $waiverReason = 'Verified military child under 18';
        }

        return [
            'fee_amount' => round($feeAmount, 2),
            'waiver_amount' => round($waiverAmount, 2),
            'payable_amount' => round($feeAmount - $waiverAmount, 2),
            'waiver_reason' => $waiverReason,
        ];
    }

    public function age(CarePatient $patient): int
    {
        if ($patient->date_of_birth === null) {
            return 0;
        }

        return Carbon::parse($patient->date_of_birth->toDateString())
            ->timezone('Africa/Lagos')
            ->diffInYears(Carbon::now('Africa/Lagos'));
    }

    private function baseFee(string $category): float
    {
        return match ($category) {
            'military' => self::MILITARY_FEE,
            'dependant' => self::DEPENDANT_FEE,
            default => self::CIVILIAN_FEE,
        };
    }
}
