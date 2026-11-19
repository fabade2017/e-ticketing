<?php

namespace App\Shared\Identity;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Support\Collection;

class HospitalAccess
{
    public function hospitalsFor(User $user): Collection
    {
        if ($user->hasRole('SuperAdmin')) {
            return Hospital::active()->orderBy('name')->get();
        }

        return $user->hospitals()
            ->wherePivot('is_active', true)
            ->where('hospitals.is_active', true)
            ->orderBy('hospitals.name')
            ->get();
    }

    public function canAccess(User $user, int $hospitalId): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hospitals()
            ->where('hospitals.id', $hospitalId)
            ->wherePivot('is_active', true)
            ->where('hospitals.is_active', true)
            ->exists();
    }
}
