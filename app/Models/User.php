<?php

namespace App\Models;

use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'service_number',
        'rank',
        'unit',
        'department_id',
        'current_hospital_id',
        'phone',
        'face_embedding',
        'face_enrolled_at',
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token', 'face_embedding'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'face_enrolled_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'face_embedding' => 'array',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class)
            ->withPivot(['local_role', 'is_active'])
            ->withTimestamps();
    }

    public function currentHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'current_hospital_id');
    }

    public function suiteNotifications(): MorphMany
    {
        return $this->morphMany(SuiteNotification::class, 'notifiable');
    }

    public function carePatient(): HasOne
    {
        return $this->hasOne(CarePatient::class, 'linked_user_id');
    }

    public function sponsoredCarePatients(): BelongsToMany
    {
        return $this->belongsToMany(CarePatient::class, 'care_patient_sponsors', 'sponsor_user_id', 'patient_id')
            ->withPivot(['relationship', 'is_approved'])
            ->withTimestamps();
    }

    public function canAccessHospital(int $hospitalId): bool
    {
        if ($this->hasRole('SuperAdmin')) {
            return true;
        }

        return $this->hospitals()
            ->where('hospitals.id', $hospitalId)
            ->wherePivot('is_active', true)
            ->where('hospitals.is_active', true)
            ->exists();
    }

    public function hasFaceEnrolled(): bool
    {
        return ! empty($this->face_embedding);
    }
}
