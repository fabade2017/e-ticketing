<?php

namespace App\Modules\Maintenance\Services;

use App\Models\AuditTrail;
use App\Models\Ticket;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\MaintOverdueFlag;
use App\Modules\Maintenance\Models\MaintPreventiveSchedule;
use App\Modules\Maintenance\Models\MaintServiceLog;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $actor): MaintWorkOrder
    {
        if (! $actor->can('use maintenance')) {
            throw new AuthorizationException('You cannot create maintenance work orders.');
        }

        $hospitalId = (int) ($attributes['hospital_id'] ?? $actor->current_hospital_id ?? 0);

        if (! $actor->canAccessHospital($hospitalId)) {
            throw new AuthorizationException('You cannot create work orders for this hospital.');
        }

        $equipment = Equipment::findOrFail((int) $attributes['equipment_id']);

        if ($equipment->hospital_id !== $hospitalId) {
            throw ValidationException::withMessages(['equipment_id' => 'The selected equipment belongs to another hospital.']);
        }

        return DB::transaction(function () use ($attributes, $actor, $equipment, $hospitalId): MaintWorkOrder {
            $workOrder = MaintWorkOrder::create([
                'work_order_number' => $attributes['work_order_number'] ?? 'MWO-'.strtoupper(Str::random(8)),
                'hospital_id' => $hospitalId,
                'equipment_id' => $equipment->id,
                'fault_id' => $attributes['fault_id'] ?? null,
                'ticket_id' => $attributes['ticket_id'] ?? null,
                'fault_code' => $attributes['fault_code'] ?? null,
                'title' => $attributes['title'] ?? 'Corrective maintenance',
                'description' => $attributes['description'] ?? null,
                'priority' => $attributes['priority'] ?? 'medium',
                'status' => 'pending',
                'requested_by' => $actor->id,
                'due_at' => $attributes['due_at'] ?? null,
            ]);

            MaintServiceLog::create([
                'work_order_id' => $workOrder->id,
                'user_id' => $actor->id,
                'action' => 'opened',
                'note' => $workOrder->title,
                'metadata' => ['ticket_id' => $workOrder->ticket_id],
                'created_at' => now(),
            ]);

            AuditTrail::record('maintenance.work_order_opened', $workOrder, [
                'equipment_id' => $equipment->id,
                'ticket_id' => $workOrder->ticket_id,
            ], $hospitalId);

            return $workOrder;
        });
    }

    public function fromTicket(Ticket $ticket, User $actor, array $attributes = []): MaintWorkOrder
    {
        return $this->create(array_merge([
            'hospital_id' => $actor->current_hospital_id,
            'ticket_id' => $ticket->id,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
        ], $attributes), $actor);
    }

    public function assign(MaintWorkOrder $workOrder, User $technician, User $actor): MaintWorkOrder
    {
        if (! $actor->can('use maintenance')) {
            throw new AuthorizationException('You cannot assign work orders.');
        }

        return DB::transaction(function () use ($workOrder, $technician, $actor): MaintWorkOrder {
            $workOrder->forceFill([
                'assigned_to' => $technician->id,
                'status' => 'assigned',
                'started_at' => $workOrder->started_at ?? now(),
            ])->save();

            MaintServiceLog::create([
                'work_order_id' => $workOrder->id,
                'user_id' => $actor->id,
                'action' => 'assigned',
                'note' => "Assigned to {$technician->name}",
                'metadata' => ['technician_id' => $technician->id],
                'created_at' => now(),
            ]);

            AuditTrail::record('maintenance.work_order_assigned', $workOrder, [
                'technician_id' => $technician->id,
            ], $workOrder->hospital_id);

            return $workOrder;
        });
    }

    public function complete(MaintWorkOrder $workOrder, User $actor): MaintWorkOrder
    {
        if (! $actor->can('use maintenance')) {
            throw new AuthorizationException('You cannot complete work orders.');
        }

        return DB::transaction(function () use ($workOrder, $actor): MaintWorkOrder {
            $workOrder->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();

            MaintOverdueFlag::where('work_order_id', $workOrder->id)
                ->whereNull('cleared_at')
                ->update(['cleared_at' => now()]);

            MaintServiceLog::create([
                'work_order_id' => $workOrder->id,
                'user_id' => $actor->id,
                'action' => 'completed',
                'note' => 'Work order completed',
                'metadata' => [],
                'created_at' => now(),
            ]);

            AuditTrail::record('maintenance.work_order_completed', $workOrder, [], $workOrder->hospital_id);

            return $workOrder;
        });
    }

    public function generatePreventive(MaintPreventiveSchedule $schedule, User $actor): MaintWorkOrder
    {
        $workOrder = $this->create([
            'hospital_id' => $schedule->hospital_id,
            'equipment_id' => $schedule->equipment_id,
            'title' => $schedule->title,
            'description' => 'Automatically generated from preventive maintenance schedule.',
            'priority' => 'medium',
            'due_at' => now()->addDays(7),
        ], $actor);

        $schedule->forceFill([
            'last_work_order_id' => $workOrder->id,
            'next_due_at' => now()->addDays($schedule->interval_days),
        ])->save();

        return $workOrder;
    }
}
