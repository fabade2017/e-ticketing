<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Services\CareFeeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CareFeeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superadmin_can_update_care_registration_fees(): void
    {
        $superAdmin = User::where('email', 'superadmin@army.mil.ng')->firstOrFail();

        $this->actingAs($superAdmin)->post('/admin/settings/fees', [
            'care_military_fee' => 6500,
            'care_civilian_fee' => '12500.50',
            'care_dependant_fee' => 3000,
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Care registration fees updated.');

        $this->assertSame('6500.00', SystemSetting::getValue('care_military_fee'));
        $this->assertSame('12500.50', SystemSetting::getValue('care_civilian_fee'));
        $this->assertSame(3000.0, SystemSetting::careFees()['dependant']);
    }

    public function test_non_superadmin_cannot_update_care_registration_fees(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->firstOrFail();

        $this->actingAs($admin)->post('/admin/settings/fees', [
            'care_military_fee' => 1,
            'care_civilian_fee' => 1,
            'care_dependant_fee' => 1,
        ])->assertForbidden();

        $this->assertSame(5000.0, SystemSetting::careFees()['military']);
    }

    public function test_care_fee_updates_are_validated(): void
    {
        $superAdmin = User::where('email', 'superadmin@army.mil.ng')->firstOrFail();

        $this->actingAs($superAdmin)->post('/admin/settings/fees', [
            'care_military_fee' => -1,
            'care_civilian_fee' => 10000,
            'care_dependant_fee' => 2500,
        ])->assertSessionHasErrors('care_military_fee');
    }

    public function test_care_fee_service_uses_managed_fees(): void
    {
        SystemSetting::setValue('care_civilian_fee', '12345.67');
        $patient = CarePatient::factory()->create([
            'hospital_id' => Hospital::factory()->create()->id,
            'category' => 'civilian',
        ]);

        $quote = app(CareFeeService::class)->quote($patient, ['category' => 'civilian']);

        $this->assertSame(12345.67, $quote['fee_amount']);
        $this->assertSame(12345.67, $quote['payable_amount']);
    }

    public function test_care_fees_are_shared_with_web_portal_and_meta_api(): void
    {
        SystemSetting::setValue('care_military_fee', '7000');
        $superAdmin = User::where('email', 'superadmin@army.mil.ng')->firstOrFail();

        $this->actingAs($superAdmin)->get('/admin/settings')
            ->assertInertia(fn ($page) => $page
                ->where('settings.care_fees.military', 7000)
                ->where('careFees.civilian', 10000)
            );

        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/meta')
            ->assertOk()
            ->assertJsonPath('care_registration_fees.military', 7000)
            ->assertJsonPath('care_registration_fees.dependant', 2500);
    }
}
