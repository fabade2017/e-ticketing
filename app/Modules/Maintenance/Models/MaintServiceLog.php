<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\MaintServiceLogFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintServiceLog extends Model
{
    /** @use HasFactory<MaintServiceLogFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return MaintServiceLogFactory::new();
    }

    public $timestamps = false;

    protected $fillable = ['work_order_id', 'user_id', 'action', 'note', 'metadata', 'created_at'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintWorkOrder::class, 'work_order_id');
    }
}
