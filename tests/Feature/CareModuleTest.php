<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Care\Models\CareLabImage;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePatientSponsor;
use App\Modules\Care\Models\CareReferral;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Services\CareAppointmentService;
use App\Modules\Care\Services\CareBillingService;
use App\Modules\Care\Services\CareClinicalService;
use App\Modules\Care\Services\CareDutyCertificateService;
use App\Modules\Care\Services\CareFeeService;
use App\Modules\Care\Services\CareRegistrationService;
use App\Modules\Care\Services\CareReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CareModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
    }

    public function test_military_registration_waives_fee_after_identity_and_evidence_checks(): void
    {
        $hospital = $this->hospital();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $patient = $this->patient($hospital, ['category' => 'military']);

        $registration = app(CareRegistrationService::class)->create($patient, $reception);
        $this->assertSame(5000.0, (float) $registration->payable_amount);

        $reviewed = app(CareRegistrationService::class)->review($registration, $reception, 'approved', [
            'identity_verified' => true,
            'evidence_verified' => true,
        ]);

        $this->assertSame('approved', $reviewed->status);
        $this->assertSame(5000.0, (float) $reviewed->waiver_amount);
        $this->assertSame(0.0, (float) $reviewed->payable_amount);
    }

    public function test_dependant_registration_requires_approved_sponsor(): void
    {
        $hospital = $this->hospital();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $sponsor = $this->userWithRoles($hospital, 'Patient');
        $patient = $this->patient($hospital, [
            'category' => 'dependant',
            'date_of_birth' => now()->subYears(10)->toDateString(),
        ]);
        $sponsorRecord = CarePatientSponsor::factory()->create([
            'patient_id' => $patient->id,
            'sponsor_user_id' => $sponsor->id,
            'relationship' => 'child',
        ]);

        $registration = app(CareRegistrationService::class)->create($patient, $reception, [
            'sponsor_id' => $sponsorRecord->id,
        ]);

        try {
            app(CareRegistrationService::class)->review($registration, $reception, 'approved', [
                'identity_verified' => true,
                'evidence_verified' => true,
                'sponsor_approved' => false,
            ]);
            $this->fail('Registration approval should require an approved sponsor.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sponsor_approved', $exception->errors());
        }

        app(CareRegistrationService::class)->approveSponsor($sponsorRecord, $sponsor);

        $reviewed = app(CareRegistrationService::class)->review($registration, $reception, 'approved', [
            'identity_verified' => true,
            'evidence_verified' => true,
            'sponsor_approved' => true,
        ]);

        $this->assertSame('approved', $reviewed->status);
        $this->assertSame(0.0, (float) $reviewed->payable_amount);
    }

    public function test_appointment_request_is_not_a_booking_until_reception_confirms(): void
    {
        $hospital = $this->hospital();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $portalUser = $this->userWithRoles($hospital, 'Patient');
        $patient = CarePatient::factory()->linkedTo($portalUser)->create([
            'hospital_id' => $hospital->id,
            'category' => 'civilian',
        ]);

        $appointment = app(CareAppointmentService::class)->request($patient, $portalUser, [
            'requested_for' => now()->addDay()->toDateTimeString(),
            'reason' => 'Review symptoms',
        ]);

        $this->assertSame('requested', $appointment->status);
        $this->assertNull($appointment->confirmed_at);

        $confirmed = app(CareAppointmentService::class)->confirm($appointment, $reception);

        $this->assertSame('confirmed', $confirmed->status);
        $this->assertSame($reception->id, $confirmed->confirmed_by);
    }

    public function test_clinical_lab_and_summary_flow_publishes_only_explicit_data(): void
    {
        $hospital = $this->hospital();
        $doctor = $this->userWithRoles($hospital, 'Doctor');
        $lab = $this->userWithRoles($hospital, 'Lab');
        $patient = $this->patient($hospital);

        $clinical = app(CareClinicalService::class);
        $visit = $clinical->startVisit($patient, $doctor);
        $clinical->recordVitals($visit, $doctor, ['temperature_c' => 36.8, 'pulse' => 72]);
        $clinical->addDiagnosis($visit, $patient, $doctor, ['description' => 'Malaria']);
        $order = $clinical->orderLab($visit, $patient, $doctor, ['test_name' => 'Malaria parasite']);
        $result = $clinical->createLabResult($order, $lab);

        $this->assertSame('draft', $result->status);

        $published = $clinical->publishLabResult($result, $lab, 'Parasite seen.');
        $visit = $clinical->publishSummary($visit, $doctor, 'Diagnosed and treated.');
        $visit = $clinical->completeVisit($visit, $doctor);

        $this->assertSame('published', $published->status);
        $this->assertSame('completed', $published->labOrder->status);
        $this->assertSame('Diagnosed and treated.', $visit->published_summary);
        $this->assertSame('completed', $visit->status);
        $this->assertNotNull($visit->completed_at);
        $this->assertSame(1, CareLabResult::published()->count());
    }

    public function test_manual_payment_is_not_counted_until_accounts_confirms_credit(): void
    {
        $hospital = $this->hospital();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $accounts = $this->userWithRoles($hospital, 'Accounts');
        $portalUser = $this->userWithRoles($hospital, 'Patient');
        $patient = CarePatient::factory()->linkedTo($portalUser)->create([
            'hospital_id' => $hospital->id,
            'category' => 'civilian',
        ]);
        $registration = CareRegistration::factory()->approved()->create([
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
        ]);

        $billing = app(CareBillingService::class);
        $invoice = $billing->createInvoice($registration, $reception);
        $payment = $billing->submitPayment($invoice, $portalUser, [
            'amount' => 10000,
            'bank_reference' => 'TRF-12345',
        ]);

        $this->assertSame('pending', $payment->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);

        $billing->verifyPayment($payment, $accounts, [
            'bank_reference' => 'TRF-12345',
            'credited' => true,
        ]);

        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(10000.0, (float) $invoice->fresh()->amount_paid);
    }

    public function test_duty_certificate_replacement_and_administrative_update_are_scoped(): void
    {
        $hospital = $this->hospital();
        $doctor = $this->userWithRoles($hospital, 'Doctor');
        $records = $this->userWithRoles($hospital, 'RecordsOfficer');
        $patient = $this->patient($hospital);

        $service = app(CareDutyCertificateService::class);
        $first = $service->issue($patient, $doctor, [
            'status' => 'FIT',
            'statement' => 'Fit for duty.',
        ]);
        $second = $service->issue($patient, $doctor, [
            'status' => 'RESTRICTED',
            'statement' => 'Restricted duties.',
        ]);

        $this->assertFalse((bool) $first->fresh()->is_current);
        $this->assertSame($second->id, $first->fresh()->superseded_by);
        $this->assertSame('RESTRICTED', $service->statusFor($patient));

        $updated = $service->updateAdministrative($second, $records, [
            'administrative_note' => 'Filed in records.',
        ]);

        $this->assertSame('Filed in records.', $updated->administrative_note);

        try {
            $service->updateAdministrative($second, $records, ['status' => 'FIT']);
            $this->fail('Records Officers cannot change duty status.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('administrative_note', $exception->errors());
        }
    }

    public function test_monthly_report_counts_completed_visits_and_tests(): void
    {
        $hospital = $this->hospital();
        $admin = $this->userWithRoles($hospital, 'HospitalAdmin');
        $patient = $this->patient($hospital);

        CareVisit::factory()->completed()->create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patient->id,
        ]);
        CareLabOrder::factory()->completed()->create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patient->id,
        ]);

        $report = app(CareReportService::class)->generate(now()->month, now()->year, $hospital->id, $admin);

        $this->assertSame(1, $report->completed_visits);
        $this->assertSame(1, $report->patients_tested);
        $this->assertSame(1, $report->tests_performed);
        $this->assertStringContainsString('completed_visits', app(CareReportService::class)->csv($report));
    }

    public function test_reception_can_review_registration_over_http(): void
    {
        $hospital = $this->hospital();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $patient = $this->patient($hospital);
        $registration = CareRegistration::factory()->create([
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
        ]);

        $response = $this->actingAs($reception)->post("/care/registrations/{$registration->id}/review", [
            'decision' => 'approved',
            'identity_verified' => true,
            'evidence_verified' => true,
        ]);

        $response->assertRedirect();
        $this->assertSame('approved', $registration->fresh()->status);
    }

    public function test_sponsor_cannot_view_published_clinical_results(): void
    {
        $hospital = $this->hospital();
        $reception = $this->userWithRoles($hospital, 'Reception');
        $portalUser = $this->userWithRoles($hospital, 'Patient');
        $sponsor = $this->userWithRoles($hospital, 'Patient');
        $patient = CarePatient::factory()->linkedTo($portalUser)->create([
            'hospital_id' => $hospital->id,
        ]);
        CarePatientSponsor::factory()->approved($reception)->create([
            'patient_id' => $patient->id,
            'sponsor_user_id' => $sponsor->id,
        ]);

        $response = $this->actingAs($sponsor)->get("/portal/results?patient_id={$patient->id}");

        $response->assertForbidden();
    }

    public function test_patient_portal_provisions_a_linked_patient_on_first_visit(): void
    {
        $hospital = $this->hospital();
        $portalUser = $this->userWithRoles($hospital, 'Patient');

        $this->actingAs($portalUser)->get('/portal')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('patient.linked_user_id', $portalUser->id)
                ->where('patient.hospital_id', $hospital->id)
                ->where('patient.category', 'civilian')
            );

        $this->assertSame(1, CarePatient::where('linked_user_id', $portalUser->id)->count());

        $this->actingAs($portalUser->fresh())->get('/portal')->assertOk();

        $this->assertSame(1, CarePatient::where('linked_user_id', $portalUser->id)->count());
    }

    public function test_patient_portal_does_not_provision_for_an_explicit_missing_patient(): void
    {
        $hospital = $this->hospital();
        $portalUser = $this->userWithRoles($hospital, 'Patient');

        $this->actingAs($portalUser)->get('/portal?patient_id=999999')->assertNotFound();

        $this->assertSame(0, CarePatient::where('linked_user_id', $portalUser->id)->count());
    }

    public function test_user_without_care_workspace_permission_cannot_open_care_module(): void
    {
        $hospital = $this->hospital();
        $user = $this->userWithRoles($hospital, 'Soldier');

        $response = $this->actingAs($user)->get('/care');

        $response->assertForbidden();
    }

    public function test_child_waiver_stops_at_eighteenth_birthday_in_lagos_time(): void
    {
        $hospital = $this->hospital();
        $fees = app(CareFeeService::class);
        $checks = [
            'category' => 'dependant',
            'relationship' => 'child',
            'requires_sponsor' => true,
            'identity_verified' => true,
            'evidence_verified' => true,
            'sponsor_approved' => true,
        ];

        $seventeen = $this->patient($hospital, [
            'category' => 'dependant',
            'date_of_birth' => now('Africa/Lagos')->subYears(17)->toDateString(),
        ]);
        $eighteen = $this->patient($hospital, [
            'category' => 'dependant',
            'date_of_birth' => now('Africa/Lagos')->subYears(18)->toDateString(),
        ]);

        $this->assertSame(0.0, (float) $fees->quote($seventeen, $checks)['payable_amount']);
        $this->assertSame(2500.0, (float) $fees->quote($eighteen, $checks)['payable_amount']);
    }

    public function test_care_role_permission_matrix(): void
    {
        $matrix = [
            'Patient' => ['use care portal'],
            'Reception' => ['use care workspace', 'approve registrations'],
            'Accounts' => ['use care workspace', 'verify payments'],
            'Nurse' => ['use care workspace', 'manage admissions', 'publish consultation summaries', 'view clinical records'],
            'Doctor' => ['use care workspace', 'certify duty status', 'publish consultation summaries', 'view clinical records'],
            'Lab' => ['use care workspace', 'publish lab results', 'upload lab images', 'view clinical records'],
            'Pharmacy' => ['use care workspace', 'view clinical records'],
            'RecordsOfficer' => ['use care workspace', 'maintain duty records', 'view clinical records'],
            'HospitalAdmin' => ['use care workspace', 'approve registrations', 'verify payments', 'manage admissions', 'manage referrals', 'view clinical records', 'view reports'],
            'NetworkAdmin' => ['use care workspace', 'view clinical records', 'view reports', 'view network reports'],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = Role::findByName($roleName);

            foreach ($permissions as $permission) {
                $this->assertTrue($role->hasPermissionTo($permission), "{$roleName} should have {$permission}");
            }
        }

        $this->assertFalse(Role::findByName('Patient')->hasPermissionTo('use care workspace'));
        $this->assertFalse(Role::findByName('Reception')->hasPermissionTo('certify duty status'));
        $this->assertFalse(Role::findByName('RecordsOfficer')->hasPermissionTo('certify duty status'));
        $this->assertFalse(Role::findByName('Lab')->hasPermissionTo('verify payments'));
    }

    public function test_referral_requires_receiving_hospital_acceptance_and_alerts_previous_doctor(): void
    {
        $fromHospital = $this->hospital();
        $toHospital = $this->hospital();
        $fromAdmin = $this->userWithRoles($fromHospital, 'HospitalAdmin');
        $toAdmin = $this->userWithRoles($toHospital, 'HospitalAdmin');
        $previousDoctor = $this->userWithRoles($fromHospital, 'Doctor');
        $patient = $this->patient($fromHospital);
        $visit = CareVisit::factory()->completed()->create([
            'hospital_id' => $fromHospital->id,
            'patient_id' => $patient->id,
            'clinician_id' => $previousDoctor->id,
        ]);

        $clinical = app(CareClinicalService::class);
        $referral = $clinical->refer($patient, $toHospital, $fromAdmin, 'Needs specialist review.', $visit);

        $this->assertSame('pending', $referral->status);
        $this->assertSame(1, SuiteNotification::where('notifiable_id', $previousDoctor->id)
            ->where('type', 'care.referral_previous_doctor')
            ->count());

        $accepted = $clinical->acceptReferral($referral, $toAdmin);

        $this->assertSame('accepted', $accepted->status);
        $this->assertSame($toAdmin->id, $accepted->accepted_by);
        $this->assertSame(1, CareReferral::accepted()->count());
    }

    public function test_lab_image_upload_validates_type_size_and_limit(): void
    {
        $hospital = $this->hospital();
        $lab = $this->userWithRoles($hospital, 'Lab');
        $patient = $this->patient($hospital);
        $order = CareLabOrder::factory()->create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patient->id,
        ]);
        $result = CareLabResult::factory()->create([
            'lab_order_id' => $order->id,
            'hospital_id' => $hospital->id,
            'patient_id' => $patient->id,
        ]);

        $clinical = app(CareClinicalService::class);
        $attached = $clinical->attachLabImage($result, $lab, UploadedFile::fake()->image('scan.png', 400, 400));

        $this->assertSame(1, CareLabImage::where('lab_result_id', $result->id)->count());
        $this->assertTrue(Storage::disk('public')->exists($attached->path));

        try {
            $clinical->attachLabImage($result, $lab, UploadedFile::fake()->create('scan.gif', 100, 'image/gif'));
            $this->fail('Non JPG/PNG lab images should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        try {
            $clinical->attachLabImage($result, $lab, UploadedFile::fake()->create('large.png', 5121, 'image/png'));
            $this->fail('Lab images over 5 MB should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $this->assertSame(1, CareLabImage::where('lab_result_id', $result->id)->count());
    }

    private function hospital(): Hospital
    {
        return Hospital::factory()->create();
    }

    private function patient(Hospital $hospital, array $attributes = []): CarePatient
    {
        return CarePatient::factory()->create(array_merge([
            'hospital_id' => $hospital->id,
            'category' => 'civilian',
        ], $attributes));
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
