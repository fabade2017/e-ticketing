<?php

namespace App\Modules\Supply\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\Supply\Models\SupplyApprovalRequest;
use App\Modules\Supply\Models\SupplyBatch;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyItemCategory;
use App\Modules\Supply\Models\SupplyStockBalance;
use App\Modules\Supply\Services\StockLedgerService;
use App\Modules\Supply\Services\SupplyAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SupplyController extends Controller
{
    public function index(Request $request, SupplyAlertService $alerts)
    {
        $user = auth()->user();
        $includeAll = $this->canSeeAll($user, $request);
        $hospitalIds = $this->hospitalIds($user, $includeAll);

        $query = SupplyStockBalance::with(['item:id,name,sku,unit'])
            ->whereIn('hospital_id', $hospitalIds->all())
            ->when($request->input('search'), function ($query, string $search): void {
                $query->whereHas('item', fn ($itemQuery) => $itemQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"));
            })
            ->when($request->boolean('low_stock'), function ($query): void {
                $query->whereExists(function ($exists): void {
                    $exists->select(DB::raw(1))
                        ->from('supply_reorder_rules')
                        ->whereColumn('supply_reorder_rules.item_id', 'supply_stock_balances.item_id')
                        ->whereColumn('supply_reorder_rules.hospital_id', 'supply_stock_balances.hospital_id')
                        ->where('supply_reorder_rules.is_active', true)
                        ->whereColumn('supply_stock_balances.quantity_on_hand', '<', 'supply_reorder_rules.minimum_quantity');
                });
            });

        return Inertia::render('Supply/Index', [
            'balances' => $query->latest()->paginate(20)->withQueryString(),
            'items' => SupplyItem::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit']),
            'categories' => SupplyItemCategory::active()->orderBy('name')->get(['id', 'name']),
            'approvals' => SupplyApprovalRequest::pending()
                ->whereIn('hospital_id', $hospitalIds->all())
                ->with('subject')
                ->latest()
                ->limit(10)
                ->get(),
            'insights' => $alerts->metrics($user, $includeAll),
            'filters' => $request->only(['search', 'low_stock', 'scope']),
            'canSeeAll' => $user->hasRole('SuperAdmin') || $user->can('view network reports'),
            'isAll' => $includeAll,
            'currentHospital' => $user->currentHospital ? [
                'id' => $user->currentHospital->id,
                'name' => $user->currentHospital->name,
            ] : null,
        ]);
    }

    public function showItem(Request $request, SupplyItem $item)
    {
        $user = auth()->user();
        $includeAll = $this->canSeeAll($user, $request);
        $hospitalIds = $this->hospitalIds($user, $includeAll);

        return Inertia::render('Supply/Show', [
            'item' => $item,
            'balances' => SupplyStockBalance::where('item_id', $item->id)
                ->whereIn('hospital_id', $hospitalIds->all())
                ->with('hospital:id,name,code')
                ->get(),
            'batches' => SupplyBatch::where('item_id', $item->id)
                ->whereIn('hospital_id', $hospitalIds->all())
                ->latest()
                ->get(),
        ]);
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:supply_items,sku',
            'supply_item_category_id' => ['required', 'integer', Rule::exists('supply_item_categories', 'id')->where('is_active', true)],
            'unit' => ['required', Rule::in(SystemSetting::options('supply_units'))],
            'description' => 'nullable|string',
        ]);

        $category = SupplyItemCategory::findOrFail($validated['supply_item_category_id']);
        $validated['category'] = $category->name;

        SupplyItem::create($validated);

        return back()->with('success', 'Supply item created.');
    }

    public function receive(Request $request, StockLedgerService $ledger)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:supply_items,id',
            'hospital_id' => 'nullable|integer',
            'quantity' => 'required|numeric|min:0.001',
            'supplier' => 'nullable|string|max:255',
            'document_no' => 'nullable|string|max:100',
            'batch_code' => 'nullable|string|max:100',
            'expires_on' => 'nullable|date',
            'is_damaged' => 'nullable|boolean',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $movement = $ledger->receive(
            SupplyItem::findOrFail((int) $validated['item_id']),
            $this->resolveHospitalId($request),
            (float) $validated['quantity'],
            auth()->user(),
            $validated,
        );

        return back()->with('success', $movement->status === 'approved'
            ? 'Goods received and stock updated.'
            : 'Goods received and sent for approval.');
    }

    public function issue(Request $request, StockLedgerService $ledger)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:supply_items,id',
            'hospital_id' => 'nullable|integer',
            'batch_id' => 'nullable|exists:supply_batches,id',
            'quantity' => 'required|numeric|min:0.001',
            'recipient_type' => 'required|string|max:100',
            'recipient_id' => 'nullable|integer',
            'purpose' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $movement = $ledger->issue(
            SupplyItem::findOrFail((int) $validated['item_id']),
            $this->resolveHospitalId($request),
            (float) $validated['quantity'],
            auth()->user(),
            $validated,
        );

        return back()->with('success', $movement->status === 'approved'
            ? 'Stock issued and balance updated.'
            : 'Stock issue sent for approval.');
    }

    public function reconcile(Request $request, StockLedgerService $ledger)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:supply_items,id',
            'hospital_id' => 'nullable|integer',
            'expected_quantity' => 'required|numeric|min:0',
            'counted_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $ledger->reconcile(
            SupplyItem::findOrFail((int) $validated['item_id']),
            $this->resolveHospitalId($request),
            (float) $validated['expected_quantity'],
            (float) $validated['counted_quantity'],
            auth()->user(),
            $validated,
        );

        return back()->with('success', 'Reconciliation recorded.');
    }

    private function canSeeAll(User $user, Request $request): bool
    {
        return $user->hasRole('SuperAdmin')
            || ($request->input('scope') === 'all' && $user->can('view network reports'));
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
            abort(403, 'You cannot manage stock for this hospital.');
        }

        return $hospitalId;
    }
}
