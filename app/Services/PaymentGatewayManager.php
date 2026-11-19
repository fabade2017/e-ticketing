<?php

namespace App\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\ValidationException;

class PaymentGatewayManager
{
    public function __construct(public Container $container) {}

    public function options(): array
    {
        return collect($this->configurations())
            ->map(fn (array $config, string $key): array => [
                'key' => $key,
                'label' => $config['label'] ?? $key,
                'requires_bank_reference' => (bool) ($config['requires_bank_reference'] ?? false),
                'requires_receipt' => (bool) ($config['requires_receipt'] ?? false),
                'auto_verify' => (bool) ($config['auto_verify'] ?? false),
            ])
            ->values()
            ->all();
    }

    public function defaultKey(): string
    {
        $default = (string) config('payments.default', 'manual_transfer');
        $configurations = $this->configurations();

        if (isset($configurations[$default])) {
            return $default;
        }

        return (string) (array_key_first($configurations) ?? 'manual_transfer');
    }

    public function gateway(?string $key = null): PaymentGateway
    {
        $key ??= $this->defaultKey();
        $configurations = $this->configurations();

        if (! isset($configurations[$key])) {
            throw ValidationException::withMessages(['gateway' => 'Selected payment gateway is not available.']);
        }

        $class = $configurations[$key]['class'] ?? null;

        if (! is_string($class) || ! is_a($class, PaymentGateway::class, true)) {
            throw ValidationException::withMessages(['gateway' => 'Selected payment gateway is not configured correctly.']);
        }

        return $this->container->make($class, [
            'config' => array_merge(['key' => $key], $configurations[$key]),
        ]);
    }

    private function configurations(): array
    {
        return collect(config('payments.gateways', []))
            ->filter(fn (array $config): bool => (bool) ($config['enabled'] ?? true))
            ->all();
    }
}
