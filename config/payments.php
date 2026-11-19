<?php

use App\Services\ConfiguredPaymentGateway;
use App\Services\ManualTransferGateway;

return [
    'default' => env('PAYMENT_GATEWAY', 'manual_transfer'),

    'gateways' => [
        'manual_transfer' => [
            'class' => ManualTransferGateway::class,
            'label' => 'Manual bank transfer',
            'enabled' => true,
            'requires_bank_reference' => true,
            'requires_receipt' => false,
            'auto_verify' => false,
            'bank' => env('CARE_BANK_NAME'),
            'account_name' => env('CARE_BANK_ACCOUNT_NAME'),
            'account_number' => env('CARE_BANK_ACCOUNT_NUMBER'),
        ],
        'opay' => [
            'class' => ConfiguredPaymentGateway::class,
            'label' => 'OPay',
            'enabled' => (bool) env('OPAY_ENABLED', false),
            'requires_bank_reference' => false,
            'requires_receipt' => false,
            'auto_verify' => false,
            'checkout_url' => env('OPAY_CHECKOUT_URL'),
            'verification_url' => env('OPAY_VERIFICATION_URL'),
            'public_key' => env('OPAY_PUBLIC_KEY'),
            'secret_key' => env('OPAY_SECRET_KEY'),
        ],
        'paystack' => [
            'class' => ConfiguredPaymentGateway::class,
            'label' => 'Paystack',
            'enabled' => (bool) env('PAYSTACK_ENABLED', false),
            'requires_bank_reference' => false,
            'requires_receipt' => false,
            'auto_verify' => false,
            'checkout_url' => env('PAYSTACK_CHECKOUT_URL'),
            'verification_url' => env('PAYSTACK_VERIFICATION_URL'),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
        ],
    ],
];
