<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Services\CareBillingService;
use App\Services\ConfiguredPaymentGateway;
use App\Services\ManualTransferGateway;
use App\Services\PaymentGatewayManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_gateway_options_come_from_config_and_hide_disabled_providers(): void
    {
        Config::set('payments.gateways.demo_pay', [
            'class' => ManualTransferGateway::class,
            'label' => 'Demo Pay',
            'enabled' => true,
            'requires_bank_reference' => false,
            'requires_receipt' => false,
        ]);

        $options = collect(app(PaymentGatewayManager::class)->options())->pluck('key')->all();

        $this->assertContains('manual_transfer', $options);
        $this->assertContains('demo_pay', $options);
        $this->assertNotContains('opay', $options);
        $this->assertNotContains('paystack', $options);
    }

    public function test_new_gateway_can_be_added_with_config_only_and_record_payments(): void
    {
        Config::set('payments.gateways.demo_pay', [
            'class' => ConfiguredPaymentGateway::class,
            'label' => 'Demo Pay',
            'enabled' => true,
            'requires_bank_reference' => false,
            'requires_receipt' => false,
            'checkout_url' => 'https://pay.example.test/checkout/{reference}',
        ]);

        [$invoice, $portalUser] = $this->invoice();
        $gateway = app(PaymentGatewayManager::class)->gateway('demo_pay');

        $payment = app(CareBillingService::class)->submitPayment(
            $invoice,
            $portalUser,
            ['gateway' => 'demo_pay', 'amount' => 5500],
            null,
            $gateway
        );

        $this->assertSame('demo_pay', $payment->method);
        $this->assertSame('demo_pay', $payment->metadata['gateway']);
        $this->assertSame('redirect', $payment->metadata['initiation']['type']);
        $this->assertStringContainsString('checkout', $payment->metadata['initiation']['checkout_url']);
    }

    public function test_configured_gateway_can_verify_and_confirm_a_payment(): void
    {
        Config::set('payments.gateways.demo_pay', [
            'class' => ConfiguredPaymentGateway::class,
            'label' => 'Demo Pay',
            'enabled' => true,
            'requires_bank_reference' => false,
            'requires_receipt' => false,
            'verification_url' => 'https://pay.example.test/verify',
            'secret_key' => 'test-secret',
        ]);
        Http::fake([
            'pay.example.test/verify' => Http::response([
                'status' => 'success',
                'reference' => 'DEMO-PAY-123',
            ]),
        ]);

        [$invoice, $portalUser] = $this->invoice();
        $billing = app(CareBillingService::class);
        $gateway = app(PaymentGatewayManager::class)->gateway('demo_pay');
        $payment = $billing->submitPayment(
            $invoice,
            $portalUser,
            ['gateway' => 'demo_pay', 'amount' => (float) $invoice->total, 'bank_reference' => 'DEMO-PAY-123'],
            null,
            $gateway
        );

        $result = $billing->syncGatewayPayment($payment, $gateway);

        $this->assertSame('verified', $result['status']);
        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-secret'));
    }

    public function test_manual_transfer_still_requires_a_reference(): void
    {
        [$invoice, $portalUser] = $this->invoice();

        $this->actingAs($portalUser)->post("/portal/invoices/{$invoice->id}/payments", [
            'gateway' => 'manual_transfer',
            'amount' => 2500,
        ])->assertSessionHasErrors('bank_reference');
    }

    private function invoice(): array
    {
        $hospital = Hospital::factory()->create();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $portalUser = $this->userWithRoles($hospital, 'Patient');
        $patient = CarePatient::factory()->linkedTo($portalUser)->create([
            'hospital_id' => $hospital->id,
            'category' => 'civilian',
        ]);
        $registration = CareRegistration::factory()->approved()->create([
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
        ]);
        $invoice = app(CareBillingService::class)->createInvoice($registration, $reception);

        return [$invoice, $portalUser];
    }

    private function userWithRoles(Hospital $hospital, string ...$roles): User
    {
        $user = User::factory()->create(['current_hospital_id' => $hospital->id]);

        foreach ($roles as $role) {
            $user->assignRole($role);
        }

        $hospital->users()->attach($user, [
            'local_role' => $roles[0],
            'is_active' => true,
        ]);

        return $user->fresh();
    }
}
