<?php

namespace App\Services;

use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ConfiguredPaymentGateway implements PaymentGateway
{
    public function __construct(public array $config = []) {}

    public function key(): string
    {
        return $this->config['key'] ?? 'configured';
    }

    public function label(): string
    {
        return $this->config['label'] ?? 'Configured payment provider';
    }

    public function requiresBankReference(): bool
    {
        return (bool) ($this->config['requires_bank_reference'] ?? false);
    }

    public function requiresReceipt(): bool
    {
        return (bool) ($this->config['requires_receipt'] ?? false);
    }

    public function autoVerifies(): bool
    {
        return (bool) ($this->config['auto_verify'] ?? false);
    }

    public function initiate(CareInvoice $invoice, array $attributes = []): array
    {
        $reference = $attributes['payment_reference'] ?? 'PAY-'.strtoupper(Str::random(12));
        $checkoutUrl = $this->config['checkout_url'] ?? null;

        if (is_string($checkoutUrl) && $checkoutUrl !== '') {
            $checkoutUrl = strtr($checkoutUrl, [
                '{reference}' => urlencode($reference),
                '{invoice}' => urlencode($invoice->invoice_number),
                '{amount}' => urlencode((string) $invoice->total),
                '{currency}' => urlencode($invoice->currency),
            ]);
        }

        return [
            'type' => $checkoutUrl ? 'redirect' : 'configured',
            'reference' => $reference,
            'checkout_url' => $checkoutUrl,
            'provider' => $this->key(),
        ];
    }

    public function verify(CarePayment $payment): ?array
    {
        $verificationUrl = $this->config['verification_url'] ?? null;

        if (! is_string($verificationUrl) || $verificationUrl === '') {
            return null;
        }

        $response = Http::withToken($this->config['secret_key'] ?? null)
            ->timeout(15)
            ->post($verificationUrl, [
                'reference' => $payment->bank_reference ?: $payment->payment_reference,
                'payment_reference' => $payment->payment_reference,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return [
            'status' => $response->json('status') === 'success' ? 'verified' : 'pending',
            'reference' => $response->json('reference') ?: $payment->bank_reference,
            'metadata' => $response->json(),
        ];
    }
}
