<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SuiteReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.key', null);
    }

    public function test_report_page_limits_module_selector_to_permitted_modules(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->first();

        $this->actingAs($admin)->get('/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('selectedModule', 'ticketing')
                ->where('availableModules', fn ($modules) => collect($modules)->pluck('key')->contains('ticketing')
                    && ! collect($modules)->pluck('key')->contains('care')
                    && ! collect($modules)->pluck('key')->contains('all'))
                ->where('suiteSections', fn ($sections) => ! collect($sections)->pluck('key')->contains('care'))
            );
    }

    public function test_admin_cannot_export_network_scope_workbook(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->first();

        $this->actingAs($admin)->get('/reports/workbook?module=all')
            ->assertForbidden();
    }

    public function test_network_admin_can_export_network_scope_workbook(): void
    {
        $networkAdmin = $this->roleUser('NetworkAdmin');

        $this->actingAs($networkAdmin)->get('/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('availableModules', fn ($modules) => collect($modules)->pluck('key')->contains('all'))
            );

        $this->actingAs($networkAdmin)->get('/reports/workbook?module=all')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_hospital_admin_can_export_care_workbook_but_not_network_scope(): void
    {
        $hospitalAdmin = $this->roleUser('HospitalAdmin');

        $this->actingAs($hospitalAdmin)->get('/reports/workbook?module=care')
            ->assertOk();

        $this->actingAs($hospitalAdmin)->get('/reports/workbook?module=all')
            ->assertForbidden();
    }

    public function test_care_workbook_excludes_ticket_report_for_care_only_user(): void
    {
        $hospitalAdmin = $this->roleUser('HospitalAdmin');

        $response = $this->actingAs($hospitalAdmin)->get('/reports/workbook?module=care')
            ->assertOk();

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $zip->close();

        $this->assertStringContainsString('Suite Summary', $workbookXml);
        $this->assertStringNotContainsString('Ticket Report', $workbookXml);
    }

    public function test_maintenance_metrics_stay_in_current_hospital_when_scope_is_all(): void
    {
        $hospital = Hospital::first();
        $otherHospital = Hospital::factory()->create();
        $technician = $this->roleUser('MaintenanceTech');
        $localEquipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $otherEquipment = Equipment::factory()->create(['hospital_id' => $otherHospital->id]);

        MaintWorkOrder::factory()->create([
            'hospital_id' => $hospital->id,
            'equipment_id' => $localEquipment->id,
            'status' => 'pending',
            'requested_by' => $technician->id,
        ]);
        MaintWorkOrder::factory()->create([
            'hospital_id' => $otherHospital->id,
            'equipment_id' => $otherEquipment->id,
            'status' => 'pending',
            'requested_by' => $technician->id,
        ]);

        $this->actingAs($technician)->get('/dashboard?scope=all')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suiteInsights', fn ($sections) => collect($sections)->firstWhere('key', 'maintenance')['metrics'][0]['value'] === 1)
            );
    }

    public function test_ai_answers_cross_module_questions_from_suite_metrics(): void
    {
        $superAdmin = User::where('email', 'superadmin@army.mil.ng')->first();

        $response = $this->actingAs($superAdmin)->postJson('/ai/ask', ['q' => 'What is the maintenance backlog?'])
            ->assertOk()
            ->assertJsonPath('provider', 'local');

        $this->assertStringContainsString('Maintenance', $response->json('answer'));
        $this->assertStringContainsString('Open work orders', $response->json('answer'));
    }

    private function roleUser(string $role): User
    {
        $hospital = Hospital::first();
        $user = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $user->assignRole($role);
        $hospital->users()->attach($user, ['local_role' => $role, 'is_active' => true]);

        return $user->fresh();
    }
}
