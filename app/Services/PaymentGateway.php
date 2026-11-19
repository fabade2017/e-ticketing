<?php

namespace App\Services;

use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePayment;

interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    public function requiresBankReference(): bool;

    public function requiresReceipt(): bool;

    public function autoVerifies(): bool;

    public function initiate(CareInvoice $invoice, array $attributes = []): array;

    public function verify(CarePayment $payment): ?array;
}
