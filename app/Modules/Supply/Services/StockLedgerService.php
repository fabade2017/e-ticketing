<?php

namespace App\Modules\Supply\Services;

use App\Models\AuditTrail;
use App\Models\User;
use App\Modules\Supply\Models\SupplyApprovalRequest;
use App\Modules\Supply\Models\SupplyBatch;
use App\Modules\Supply\Models\SupplyGoodsIssued;
use App\Modules\Supply\Models\SupplyGoodsReceived;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyReconciliation;
use App\Modules\Supply\Models\SupplyStockBalance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockLedgerService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function receive(SupplyItem $item, int $hospitalId, float $quantity, User $actor, array $attributes = []): SupplyGoodsReceived
    {
        $this->assertCanReceive($actor);
        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use ($item, $hospitalId, $quantity, $actor, $attributes): SupplyGoodsReceived {
            $key = $attributes['idempotency_key'] ?? (string) Str::uuid();
            $batch = $this->resolveBatch($item, $hospitalId, $attributes);

            $movement = $this->firstByIdempotency(SupplyGoodsReceived::class, $hospitalId, $key, [
                'item_id' => $item->id,
                'batch_id' => $batch?->id,
                'quantity' => $quantity,
                'supplier' => $attributes['supplier'] ?? null,
                'document_no' => $attributes['document_no'] ?? null,
                'status' => 'pending',
                'received_by' => $actor->id,
            ]);

            if ($movement->wasRecentlyCreated) {
                if ($actor->can('approve stock adjustments')) {
                    $this->approveReceipt($movement, $actor);
                } else {
                    SupplyApprovalRequest::create([
                        'hospital_id' => $hospitalId,
                        'subject_type' => $movement->getMorphClass(),
                        'subject_id' => $movement->id,
                        'type' => 'stock.receipt',
                        'status' => 'pending',
                        'requested_by' => $actor->id,
                    ]);
                }

                AuditTrail::record('supply.goods_received', $movement, [
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'idempotency_key' => $key,
                ], $hospitalId);
            }

            return $movement;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function issue(SupplyItem $item, int $hospitalId, float $quantity, User $actor, array $attributes = []): SupplyGoodsIssued
    {
        $this->assertCanIssue($actor);
        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use ($item, $hospitalId, $quantity, $actor, $attributes): SupplyGoodsIssued {
            $key = $attributes['idempotency_key'] ?? (string) Str::uuid();

            $movement = $this->firstByIdempotency(SupplyGoodsIssued::class, $hospitalId, $key, [
                'item_id' => $item->id,
                'batch_id' => $attributes['batch_id'] ?? null,
                'quantity' => $quantity,
                'recipient_type' => $attributes['recipient_type'] ?? 'unit',
                'recipient_id' => $attributes['recipient_id'] ?? null,
                'purpose' => $attributes['purpose'] ?? null,
                'work_order_id' => $attributes['work_order_id'] ?? null,
                'care_visit_id' => $attributes['care_visit_id'] ?? null,
                'status' => 'pending',
                'issued_by' => $actor->id,
            ]);

            if ($movement->wasRecentlyCreated) {
                if ($actor->can('approve stock adjustments')) {
                    $this->approveIssue($movement, $actor);
                } else {
                    SupplyApprovalRequest::create([
                        'hospital_id' => $hospitalId,
                        'subject_type' => $movement->getMorphClass(),
                        'subject_id' => $movement->id,
                        'type' => 'stock.issue',
                        'status' => 'pending',
                        'requested_by' => $actor->id,
                    ]);
                }

                AuditTrail::record('supply.goods_issued', $movement, [
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'idempotency_key' => $key,
                    'work_order_id' => $attributes['work_order_id'] ?? null,
                ], $hospitalId);
            }

            return $movement;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function reconcile(SupplyItem $item, int $hospitalId, float $expected, float $counted, User $actor, array $attributes = []): SupplyReconciliation
    {
        $this->assertCanReceive($actor);

        return DB::transaction(function () use ($item, $hospitalId, $expected, $counted, $actor, $attributes): SupplyReconciliation {
            $key = $attributes['idempotency_key'] ?? (string) Str::uuid();

            $reconciliation = $this->firstByIdempotency(SupplyReconciliation::class, $hospitalId, $key, [
                'item_id' => $item->id,
                'expected_quantity' => $expected,
                'counted_quantity' => $counted,
                'difference' => $counted - $expected,
                'status' => 'pending',
                'notes' => $attributes['notes'] ?? null,
                'requested_by' => $actor->id,
            ]);

            if ($reconciliation->wasRecentlyCreated) {
                if ($actor->can('approve stock adjustments')) {
                    $balance = $this->balanceFor($item, $hospitalId, $attributes['store'] ?? 'main');
                    $balance->increment('quantity_on_hand', $counted - $expected);
                    $reconciliation->forceFill([
                        'status' => 'approved',
                        'approved_by' => $actor->id,
                        'approved_at' => now(),
                    ])->save();
                } else {
                    SupplyApprovalRequest::create([
                        'hospital_id' => $hospitalId,
                        'subject_type' => $reconciliation->getMorphClass(),
                        'subject_id' => $reconciliation->id,
                        'type' => 'stock.reconciliation',
                        'status' => 'pending',
                        'requested_by' => $actor->id,
                    ]);
                }

                AuditTrail::record('supply.reconciliation_recorded', $reconciliation, [
                    'difference' => $counted - $expected,
                ], $hospitalId);
            }

            return $reconciliation;
        });
    }

    public function approve(SupplyApprovalRequest $approval, User $actor): Model
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'This request has already been processed.']);
        }

        if (! $actor->can('approve stock adjustments')) {
            throw new AuthorizationException('You cannot approve stock adjustments.');
        }

        return DB::transaction(function () use ($approval, $actor): Model {
            $subject = $approval->subject;

            if (! $subject instanceof Model) {
                throw ValidationException::withMessages(['approval' => 'The linked record no longer exists.']);
            }

            if ($subject instanceof SupplyGoodsReceived) {
                $this->approveReceipt($subject, $actor);
            } elseif ($subject instanceof SupplyGoodsIssued) {
                $this->approveIssue($subject, $actor);
            } elseif ($subject instanceof SupplyReconciliation) {
                $balance = $this->balanceFor($subject->item, $subject->hospital_id);
                $balance->increment('quantity_on_hand', (float) $subject->difference);
                $subject->forceFill([
                    'status' => 'approved',
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ])->save();
            }

            $approval->forceFill([
                'status' => 'approved',
                'approved_by' => $actor->id,
            ])->save();

            AuditTrail::record('supply.approval_granted', $subject, [
                'approval_request_id' => $approval->id,
                'type' => $approval->type,
            ], $approval->hospital_id);

            return $subject;
        });
    }

    private function approveReceipt(SupplyGoodsReceived $movement, User $actor): void
    {
        if ($movement->status === 'approved') {
            return;
        }

        $balance = $this->balanceFor($movement->item, $movement->hospital_id);
        $balance->increment('quantity_on_hand', (float) $movement->quantity);

        if ($movement->batch_id !== null) {
            $movement->batch()->increment('quantity', (float) $movement->quantity);
        }

        $movement->forceFill([
            'status' => 'approved',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();
    }

    private function approveIssue(SupplyGoodsIssued $movement, User $actor): void
    {
        if ($movement->status === 'approved') {
            return;
        }

        $balance = SupplyStockBalance::where('item_id', $movement->item_id)
            ->where('hospital_id', $movement->hospital_id)
            ->lockForUpdate()
            ->first();

        if ($balance === null || (float) $balance->quantity_on_hand - (float) $balance->quantity_reserved < (float) $movement->quantity) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock available for this issue.']);
        }

        $balance->decrement('quantity_on_hand', (float) $movement->quantity);

        if ($movement->batch_id !== null) {
            $batch = SupplyBatch::where('id', $movement->batch_id)->lockForUpdate()->first();

            if ($batch === null || (float) $batch->quantity < (float) $movement->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient batch quantity for this issue.']);
            }

            $batch->decrement('quantity', (float) $movement->quantity);
        }

        $movement->forceFill([
            'status' => 'approved',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();
    }

    private function balanceFor(SupplyItem $item, int $hospitalId, string $store = 'main'): SupplyStockBalance
    {
        return SupplyStockBalance::firstOrCreate([
            'item_id' => $item->id,
            'hospital_id' => $hospitalId,
            'store' => $store,
        ], [
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveBatch(SupplyItem $item, int $hospitalId, array $attributes): ?SupplyBatch
    {
        if (empty($attributes['batch_code'])) {
            return null;
        }

        return SupplyBatch::firstOrCreate([
            'item_id' => $item->id,
            'hospital_id' => $hospitalId,
            'batch_code' => $attributes['batch_code'],
        ], [
            'expires_on' => $attributes['expires_on'] ?? null,
            'is_damaged' => (bool) ($attributes['is_damaged'] ?? false),
            'quantity' => 0,
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private function firstByIdempotency(string $modelClass, int $hospitalId, string $key, array $attributes): Model
    {
        try {
            return $modelClass::firstOrCreate([
                'hospital_id' => $hospitalId,
                'idempotency_key' => $key,
            ], $attributes);
        } catch (QueryException $exception) {
            $existing = $modelClass::where('hospital_id', $hospitalId)
                ->where('idempotency_key', $key)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function assertCanReceive(User $actor): void
    {
        if (! $actor->can('receive stock')) {
            throw new AuthorizationException('You cannot receive stock.');
        }
    }

    private function assertCanIssue(User $actor): void
    {
        if (! $actor->can('issue stock')) {
            throw new AuthorizationException('You cannot issue stock.');
        }
    }

    private function assertPositiveQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }
    }
}
