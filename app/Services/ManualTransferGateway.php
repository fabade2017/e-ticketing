<?php

namespace App\Services;

use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePayment;

class ManualTransferGateway implements PaymentGateway
{
    public function __construct(public array $config = []) {}

    public function key(): string
    {
        return $this->config['key'] ?? 'manual_transfer';
    }

    public function label(): string
    {
        return $this->config['label'] ?? 'Manual bank transfer';
    }

    public function requiresBankReference(): bool
    {
        return (bool) ($this->config['requires_bank_reference'] ?? true);
    }

    public function requiresReceipt(): bool
    {
        return (bool) ($this->config['requires_receipt'] ?? false);
    }

    public function autoVerifies(): bool
    {
        return false;
    }

    public function initiate(CareInvoice $invoice, array $attributes = []): array
    {
        return [
            'type' => 'manual_transfer',
            'reference' => $attributes['bank_reference'] ?? null,
            'bank_details' => array_filter([
                'bank' => $this->config['bank'] ?? null,
                'account_name' => $this->config['account_name'] ?? null,
                'account_number' => $this->config['account_number'] ?? null,
            ]),
            'instructions' => $this->config['instructions'] ?? 'Transfer the invoice amount and submit the reference for Accounts verification.',
        ];
    }

    public function verify(CarePayment $payment): ?array
    {
        return null;
    }
}
