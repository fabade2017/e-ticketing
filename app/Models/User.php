<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'service_number',
        'rank',
        'unit',
        'department_id',
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

    public function department() { return $this->belongsTo(Department::class); }
    public function hasFaceEnrolled(): bool
    {
        return !empty($this->face_embedding);
    }
}
