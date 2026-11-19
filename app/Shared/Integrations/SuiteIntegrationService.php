<?php

namespace App\Shared\Integrations;

use App\Models\AuditTrail;
use App\Models\SuiteLink;
use App\Models\Ticket;
use App\Models\User;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use App\Modules\Maintenance\Models\MaintWorkOrderPart;
use App\Modules\Maintenance\Services\WorkOrderService;
use App\Modules\Supply\Models\SupplyGoodsIssued;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Services\StockLedgerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuiteIntegrationService
{
    public function __construct(
        public WorkOrderService $workOrders,
        public StockLedgerService $stockLedger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function openWorkOrderFromTicket(Ticket $ticket, User $actor, array $attributes = []): MaintWorkOrder
    {
        $existing = MaintWorkOrder::where('ticket_id', $ticket->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        if (empty($attributes['equipment_id'])) {
            throw ValidationException::withMessages(['equipment_id' => 'Select the equipment for this work order.']);
        }

        return DB::transaction(function () use ($ticket, $actor, $attributes): MaintWorkOrder {
            $workOrder = $this->workOrders->fromTicket($ticket, $actor, $attributes);

            SuiteLink::connect($ticket, $workOrder, 'ticket_work_order');
            AuditTrail::record('suite.work_order_opened_from_ticket', $workOrder, [
                'ticket_id' => $ticket->id,
            ], $workOrder->hospital_id);

            return $workOrder;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function issueStockForWorkOrder(MaintWorkOrder $workOrder, User $actor, array $attributes): SupplyGoodsIssued
    {
        if (! $actor->canAccessHospital($workOrder->hospital_id)) {
            throw new AuthorizationException('You cannot issue stock for this hospital.');
        }

        if (empty($attributes['item_id'])) {
            throw ValidationException::withMessages(['item_id' => 'Select an item to issue.']);
        }

        $item = SupplyItem::findOrFail((int) $attributes['item_id']);
        $key = $attributes['idempotency_key'] ?? "work-order-{$workOrder->id}";

        return DB::transaction(function () use ($workOrder, $actor, $attributes, $item, $key): SupplyGoodsIssued {
            $issue = $this->stockLedger->issue($item, $workOrder->hospital_id, (float) $attributes['quantity'], $actor, [
                'idempotency_key' => $key,
                'batch_id' => $attributes['batch_id'] ?? null,
                'recipient_type' => 'work_order',
                'recipient_id' => $workOrder->id,
                'purpose' => $attributes['purpose'] ?? "Parts for {$workOrder->work_order_number}",
                'work_order_id' => $workOrder->id,
            ]);

            MaintWorkOrderPart::firstOrCreate([
                'work_order_id' => $workOrder->id,
                'supply_issue_id' => $issue->id,
            ], [
                'quantity' => $issue->quantity,
                'recorded_by' => $actor->id,
            ]);

            SuiteLink::connect($workOrder, $issue, 'work_order_stock_issue');
            AuditTrail::record('suite.stock_issued_for_work_order', $issue, [
                'work_order_id' => $workOrder->id,
                'item_id' => $item->id,
                'quantity' => $issue->quantity,
                'idempotency_key' => $key,
            ], $workOrder->hospital_id);

            return $issue;
        });
    }
}
