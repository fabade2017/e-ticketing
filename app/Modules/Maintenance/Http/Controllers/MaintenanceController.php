<?php

namespace App\Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Ticket;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\Fault;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use App\Modules\Maintenance\Services\MaintenanceAlertService;
use App\Modules\Maintenance\Services\WorkOrderService;
use App\Modules\Supply\Models\SupplyItem;
use App\Shared\Integrations\SuiteIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function index(Request $request, MaintenanceAlertService $alerts)
    {
        $user = auth()->user();
        $includeAll = $this->canSeeAll($user, $request);
        $hospitalIds = $this->hospitalIds($user, $includeAll);

        $query = MaintWorkOrder::with([
            'equipment:id,asset_id,name',
            'fault:id,code,title',
            'assignedTo:id,name',
        ])
            ->whereIn('hospital_id', $hospitalIds->all())
            ->when($request->input('search'), function ($query, string $search): void {
                $query->where(fn ($nested) => $nested
                    ->where('work_order_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%"));
            })
            ->when($request->input('status') === 'overdue', fn ($query) => $query->overdue())
            ->when($request->input('status') && $request->input('status') !== 'overdue', fn ($query, string $status) => $query->where('status', $status));

        return Inertia::render('Maintenance/Index', [
            'workOrders' => $query->latest()->paginate(20)->withQueryString(),
            'insights' => $alerts->metrics($user, $includeAll),
            'filters' => $request->only(['search', 'status', 'scope']),
            'canSeeAll' => $user->hasRole('SuperAdmin') || $user->can('view reports'),
            'isAll' => $includeAll,
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $hospitalIds = $this->hospitalIds($user, $this->canSeeAll($user, $request));

        return Inertia::render('Maintenance/Create', [
            'equipment' => Equipment::whereIn('hospital_id', $hospitalIds->all())
                ->active()
                ->orderBy('name')
                ->get(['id', 'asset_id', 'name']),
            'faults' => Fault::whereIn('hospital_id', $hospitalIds->all())
                ->where('status', 'open')
                ->latest()
                ->get(['id', 'equipment_id', 'code', 'title']),
            'tickets' => Ticket::query()
                ->when(! $user->hasRole('SuperAdmin'), fn ($query) => $query->visibleTo($user))
                ->whereNotIn('id', MaintWorkOrder::whereNotNull('ticket_id')->pluck('ticket_id'))
                ->latest()
                ->limit(50)
                ->get(['id', 'ticket_number', 'title', 'priority']),
        ]);
    }

    public function store(Request $request, WorkOrderService $workOrders)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'fault_id' => 'nullable|exists:faults,id',
            'hospital_id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
        ]);

        $workOrders->create($validated + ['hospital_id' => $this->resolveHospitalId($request)], auth()->user());

        return redirect()->route('maintenance.index')->with('success', 'Work order created.');
    }

    public function show(Request $request, MaintWorkOrder $workOrder)
    {
        $this->assertWorkOrderVisible($workOrder, $request);

        return Inertia::render('Maintenance/Show', [
            'workOrder' => $workOrder->load([
                'equipment:id,asset_id,name,unit,location',
                'fault:id,code,title',
                'parts',
                'serviceLogs' => fn ($query) => $query->latest('created_at')->limit(50),
                'overdueFlag',
            ]),
            'technicians' => User::permission('use maintenance')
                ->orderBy('name')
                ->get(['id', 'name', 'service_number']),
            'items' => SupplyItem::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit']),
        ]);
    }

    public function assign(Request $request, MaintWorkOrder $workOrder, WorkOrderService $workOrders)
    {
        $this->assertWorkOrderVisible($workOrder, $request);

        $validated = $request->validate([
            'technician_id' => 'required|exists:users,id',
        ]);

        $workOrders->assign($workOrder, User::findOrFail((int) $validated['technician_id']), auth()->user());

        return back()->with('success', 'Technician assigned.');
    }

    public function complete(Request $request, MaintWorkOrder $workOrder, WorkOrderService $workOrders)
    {
        $this->assertWorkOrderVisible($workOrder, $request);
        $workOrders->complete($workOrder, auth()->user());

        return back()->with('success', 'Work order completed.');
    }

    public function fromTicket(Request $request, Ticket $ticket, SuiteIntegrationService $integration)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'hospital_id' => 'nullable|integer',
            'due_at' => 'nullable|date',
        ]);

        $workOrder = $integration->openWorkOrderFromTicket($ticket, auth()->user(), [
            'equipment_id' => (int) $validated['equipment_id'],
            'hospital_id' => $this->resolveHospitalId($request),
            'due_at' => $validated['due_at'] ?? null,
        ]);

        return redirect()->route('maintenance.show', $workOrder)
            ->with('success', 'Work order opened from ticket.');
    }

    public function issueParts(Request $request, MaintWorkOrder $workOrder, SuiteIntegrationService $integration)
    {
        $this->assertWorkOrderVisible($workOrder, $request);

        $validated = $request->validate([
            'item_id' => 'required|exists:supply_items,id',
            'quantity' => 'required|numeric|min:0.001',
            'batch_id' => 'nullable|exists:supply_batches,id',
            'purpose' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $integration->issueStockForWorkOrder($workOrder, auth()->user(), $validated);

        return back()->with('success', 'Parts issued to work order.');
    }

    private function canSeeAll(User $user, Request $request): bool
    {
        return $user->hasRole('SuperAdmin')
            || ($request->input('scope') === 'all' && $user->can('view reports'));
    }

    private function hospitalIds(User $user, bool $includeAll): Collection
    {
        if ($includeAll) {
            return Hospital::active()->pluck('id');
        }

        return collect([$user->current_hospital_id])->filter();
    }

    private function resolveHospitalId(Request $request): int
    {
        $user = auth()->user();
        $hospitalId = (int) ($request->input('hospital_id') ?: $user->current_hospital_id);

        if ($hospitalId === 0 || ! $user->canAccessHospital($hospitalId)) {
            abort(403, 'You cannot create work orders for this hospital.');
        }

        return $hospitalId;
    }

    private function assertWorkOrderVisible(MaintWorkOrder $workOrder, Request $request): void
    {
        $user = auth()->user();

        if (! $this->hospitalIds($user, $this->canSeeAll($user, $request))->contains($workOrder->hospital_id)) {
            abort(403, 'You cannot access this work order.');
        }
    }
}
