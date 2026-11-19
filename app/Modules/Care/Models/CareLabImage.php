<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareLabImageFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareLabImage extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareLabImageFactory::new();
    }

    protected $fillable = [
        'lab_result_id', 'hospital_id', 'uploaded_by', 'path',
        'original_name', 'mime_type', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function labResult(): BelongsTo
    {
        return $this->belongsTo(CareLabResult::class, 'lab_result_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
