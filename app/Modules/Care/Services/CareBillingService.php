<?php

namespace App\Modules\Care\Services;

use App\Models\AuditTrail;
use App\Models\User;
use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePayment;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;
use App\Services\PaymentGateway;
use App\Services\PaymentGatewayManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareBillingService
{
    public function createInvoice(CareRegistration|CareVisit $source, User $actor, array $attributes = []): CareInvoice
    {
        if (! $actor->can('use care workspace')) {
            throw new AuthorizationException('You cannot create care invoices.');
        }

        $patient = $source->patient;
        $hospitalId = $source->hospital_id;

        if (! $actor->canAccessHospital($hospitalId)) {
            throw new AuthorizationException('You cannot invoice this hospital.');
        }

        $total = (float) ($attributes['total'] ?? ($source instanceof CareRegistration
            ? $source->payable_amount
            : ($attributes['total'] ?? 0)));

        $invoice = CareInvoice::create([
            'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
            'hospital_id' => $hospitalId,
            'patient_id' => $patient->id,
            'registration_id' => $source instanceof CareRegistration ? $source->id : null,
            'visit_id' => $source instanceof CareVisit ? $source->id : null,
            'currency' => $attributes['currency'] ?? 'NGN',
            'subtotal' => $attributes['subtotal'] ?? $total,
            'discount' => $attributes['discount'] ?? 0,
            'total' => $total,
            'amount_paid' => 0,
            'status' => $total <= 0 ? 'waived' : 'unpaid',
            'issued_by' => $actor->id,
            'due_at' => $attributes['due_at'] ?? now()->addDays(7),
        ]);

        AuditTrail::record('care.invoice_created', $invoice, [], $hospitalId);

        return $invoice;
    }

    public function submitPayment(CareInvoice $invoice, User $actor, array $attributes, ?UploadedFile $receipt = null, ?PaymentGateway $gateway = null): CarePayment
    {
        $this->authorizeInvoiceAccess($invoice, $actor);

        $gateway ??= app(PaymentGatewayManager::class)->gateway($attributes['gateway'] ?? null);

        $amount = (float) ($attributes['amount'] ?? 0);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'A positive payment amount is required.']);
        }

        if ($gateway->requiresReceipt() && $receipt === null) {
            throw ValidationException::withMessages(['receipt' => 'A payment receipt is required.']);
        }

        $initiation = $gateway->initiate($invoice, $attributes);
        $bankReference = $attributes['bank_reference'] ?? ($initiation['reference'] ?? null);

        if ($gateway->requiresBankReference() && empty($bankReference)) {
            throw ValidationException::withMessages(['bank_reference' => 'A transfer reference is required.']);
        }

        $receiptPath = null;
        if ($receipt !== null) {
            $receiptPath = Storage::disk('public')->putFile('care/payment-receipts', $receipt);
        }

        $payment = CarePayment::create([
            'payment_reference' => 'PAY-'.strtoupper(Str::random(8)),
            'invoice_id' => $invoice->id,
            'hospital_id' => $invoice->hospital_id,
            'patient_id' => $invoice->patient_id,
            'amount' => $amount,
            'method' => $gateway->key(),
            'bank_reference' => $bankReference,
            'receipt_path' => $receiptPath,
            'credited' => false,
            'status' => 'pending',
            'submitted_by' => $actor->id,
            'paid_at' => now(),
            'metadata' => [
                'gateway' => $gateway->key(),
                'initiation' => $initiation,
            ],
        ]);

        AuditTrail::record('care.payment_submitted', $payment, [
            'gateway' => $gateway->key(),
        ], $invoice->hospital_id);

        return $payment;
    }

    public function syncGatewayPayment(CarePayment $payment, ?PaymentGateway $gateway = null): ?array
    {
        $gateway ??= app(PaymentGatewayManager::class)->gateway($payment->method);
        $result = $gateway->verify($payment);

        if ($result === null) {
            return null;
        }

        $metadata = $payment->metadata ?? [];
        $metadata['verification'] = $result;
        $payment->forceFill(['metadata' => $metadata])->save();

        if (($result['status'] ?? null) === 'verified' && $payment->status !== 'verified') {
            $this->markPaymentVerified(
                $payment,
                (string) ($result['reference'] ?? $payment->bank_reference),
                null,
                'care.payment_verified_by_gateway'
            );
        }

        return $result;
    }

    public function verifyPayment(CarePayment $payment, User $actor, array $attributes): CarePayment
    {
        if (! $actor->can('verify payments')) {
            throw new AuthorizationException('You cannot verify care payments.');
        }

        if (! $actor->canAccessHospital($payment->hospital_id)) {
            throw new AuthorizationException('You cannot verify payments for this hospital.');
        }

        if (empty($attributes['bank_reference'])) {
            throw ValidationException::withMessages(['bank_reference' => 'Confirm the bank transfer reference.']);
        }

        if (! ($attributes['credited'] ?? false)) {
            throw ValidationException::withMessages(['credited' => 'Accounts must confirm that the transfer is actually credited.']);
        }

        return $this->markPaymentVerified($payment, (string) $attributes['bank_reference'], $actor);
    }

    private function markPaymentVerified(
        CarePayment $payment,
        string $reference,
        ?User $actor,
        string $event = 'care.payment_verified'
    ): CarePayment {
        $payment->forceFill([
            'bank_reference' => $reference,
            'credited' => true,
            'status' => 'verified',
            'verified_by' => $actor?->id,
            'verified_at' => now(),
        ])->save();

        $invoice = $payment->invoice;
        $amountPaid = (float) $invoice->payments()->verified()->sum('amount');

        $invoice->forceFill([
            'amount_paid' => $amountPaid,
            'status' => $amountPaid >= (float) $invoice->total ? 'paid' : 'partially_paid',
        ])->save();

        AuditTrail::record($event, $payment, [
            'invoice_status' => $invoice->status,
        ], $payment->hospital_id);

        return $payment;
    }

    private function authorizeInvoiceAccess(CareInvoice $invoice, User $actor): void
    {
        if ($actor->hasRole('SuperAdmin')) {
            return;
        }

        $patient = $invoice->patient;

        if ($patient->linked_user_id === $actor->id) {
            return;
        }

        if ($patient->sponsors()->approved()->where('sponsor_user_id', $actor->id)->exists()) {
            return;
        }

        if ($actor->can('use care workspace') && $actor->canAccessHospital($invoice->hospital_id)) {
            return;
        }

        throw new AuthorizationException('You cannot submit payment for this invoice.');
    }
}
