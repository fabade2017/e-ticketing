<?php

namespace App\Modules\Supply\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supply\Models\SupplyApprovalRequest;
use App\Modules\Supply\Services\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupplyApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $hospitalIds = $user->hasRole('SuperAdmin')
            ? DB::table('hospitals')->where('is_active', true)->pluck('id')
            : collect([$user->current_hospital_id])->filter();

        return Inertia::render('Supply/Approvals', [
            'approvals' => SupplyApprovalRequest::with(['subject', 'requestedBy'])
                ->whereIn('hospital_id', $hospitalIds->all())
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function approve(SupplyApprovalRequest $approval, StockLedgerService $ledger)
    {
        $ledger->approve($approval, auth()->user());

        return back()->with('success', 'Stock request approved.');
    }

    public function reject(SupplyApprovalRequest $approval)
    {
        if (! auth()->user()->can('approve stock adjustments')) {
            abort(403);
        }

        if ($approval->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        return DB::transaction(function () use ($approval) {
            $approval->forceFill([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
            ])->save();

            $subject = $approval->subject;

            if ($subject !== null) {
                $subject->forceFill(['status' => 'rejected'])->save();
            }

            return back()->with('success', 'Stock request rejected.');
        });
    }
}
