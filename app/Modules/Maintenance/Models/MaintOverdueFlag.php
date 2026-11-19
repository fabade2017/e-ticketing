<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\MaintOverdueFlagFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintOverdueFlag extends Model
{
    /** @use HasFactory<MaintOverdueFlagFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return MaintOverdueFlagFactory::new();
    }

    protected $fillable = ['work_order_id', 'flagged_at', 'cleared_at', 'notified_at'];

    protected $casts = [
        'flagged_at' => 'datetime',
        'cleared_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintWorkOrder::class, 'work_order_id');
    }
}
