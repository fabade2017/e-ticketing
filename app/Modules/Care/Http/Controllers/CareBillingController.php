<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePayment;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Services\CareBillingService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CareBillingController extends Controller
{
    public function invoices(Request $request, PaymentGatewayManager $gateways): Response
    {
        $user = auth()->user();
        $hospitalId = $user->hasRole('SuperAdmin') || ($request->input('scope') === 'all' && $user->can('view network reports'))
            ? null
            : $user->current_hospital_id;

        return Inertia::render('Care/Workspace/Invoices', [
            'invoices' => CareInvoice::with(['patient:id,first_name,last_name,network_number', 'payments'])
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status', 'scope']),
            'paymentGateways' => $gateways->options(),
            'defaultGateway' => $gateways->defaultKey(),
        ]);
    }

    public function storeInvoice(Request $request, CareBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'source_type' => ['required', 'in:registration,visit'],
            'source_id' => ['required', 'integer'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $source = $validated['source_type'] === 'registration'
            ? CareRegistration::findOrFail($validated['source_id'])
            : CareVisit::findOrFail($validated['source_id']);

        Gate::authorize('viewPatient', $source->patient);
        $billing->createInvoice($source, auth()->user(), $validated);

        return back()->with('success', 'Invoice created.');
    }

    public function storePayment(Request $request, CareInvoice $invoice, CareBillingService $billing, PaymentGatewayManager $gateways): RedirectResponse
    {
        Gate::authorize('viewInvoice', $invoice);

        $validated = $request->validate([
            'gateway' => ['required', Rule::in(collect($gateways->options())->pluck('key')->all())],
            'amount' => ['required', 'numeric', 'min:1'],
            'bank_reference' => ['nullable', 'string', 'max:150'],
            'method' => ['nullable', 'string', 'max:50'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $gateway = $gateways->gateway($validated['gateway']);

        if ($gateway->requiresBankReference()) {
            $validated['bank_reference'] = $request->validate([
                'bank_reference' => ['required', 'string', 'max:150'],
            ])['bank_reference'];
        }

        if ($gateway->requiresReceipt()) {
            $request->validate([
                'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            ]);
        }

        $payment = $billing->submitPayment($invoice, auth()->user(), $validated, $validated['receipt'] ?? null, $gateway);
        $initiation = $payment->metadata['initiation'] ?? [];

        if (($initiation['type'] ?? null) === 'redirect' && ! empty($initiation['checkout_url'])) {
            return redirect()->away($initiation['checkout_url']);
        }

        return back()->with('success', "Payment submitted via {$gateway->label()} for verification.");
    }

    public function syncPayment(CarePayment $payment, CareBillingService $billing): RedirectResponse
    {
        $result = $billing->syncGatewayPayment($payment);

        if ($result === null) {
            return back()->with('success', 'This gateway does not support automatic verification.');
        }

        return back()->with('success', 'Gateway payment status: '.($result['status'] ?? 'pending').'.');
    }

    public function verifyPayment(Request $request, CarePayment $payment, CareBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'bank_reference' => ['required', 'string', 'max:150'],
            'credited' => ['required', 'boolean', 'accepted'],
        ]);

        $billing->verifyPayment($payment, auth()->user(), $validated);

        return back()->with('success', 'Payment verified.');
    }
}
