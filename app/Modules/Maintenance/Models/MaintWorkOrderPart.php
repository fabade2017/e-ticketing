<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\MaintWorkOrderPartFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintWorkOrderPart extends Model
{
    /** @use HasFactory<MaintWorkOrderPartFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return MaintWorkOrderPartFactory::new();
    }

    protected $fillable = ['work_order_id', 'supply_issue_id', 'quantity', 'recorded_by'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintWorkOrder::class, 'work_order_id');
    }
}
