<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superadmin_can_disable_face_auth(): void
    {
        $sa = User::where('email', 'superadmin@army.mil.ng')->first();

        $response = $this->actingAs($sa)->post('/admin/settings/face', [
            'face_auth_mode' => 'local',
            'face_auth_enabled' => false,
            'cloud_provider' => 'aws',
        ]);

        $response->assertRedirect();
        $this->assertSame('0', SystemSetting::getValue('face_auth_enabled'));

        Auth::logout();

        $this->get('/login')
            ->assertInertia(fn ($page) => $page->where('systemSettings.face_auth_enabled', false));
    }

    public function test_superadmin_can_enable_face_auth(): void
    {
        SystemSetting::setValue('face_auth_enabled', '0');
        $sa = User::where('email', 'superadmin@army.mil.ng')->first();

        $this->actingAs($sa)->post('/admin/settings/face', [
            'face_auth_mode' => 'cloud',
            'face_auth_enabled' => true,
            'cloud_provider' => 'azure',
        ])->assertRedirect();

        $this->assertSame('1', SystemSetting::getValue('face_auth_enabled'));
        $this->assertSame('cloud', SystemSetting::getValue('face_auth_mode'));
        $this->assertSame('azure', SystemSetting::getValue('face_cloud_provider'));
    }

    public function test_non_superadmin_cannot_toggle(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->first();

        $this->actingAs($admin)->post('/admin/settings/face', [
            'face_auth_mode' => 'local',
            'face_auth_enabled' => false,
        ])->assertForbidden();
    }

    public function test_verify_is_blocked_when_disabled(): void
    {
        SystemSetting::setValue('face_auth_enabled', '0');
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        $soldier->update(['face_embedding' => array_fill(0, 128, 0.1)]);

        $this->postJson('/face/verify', [
            'service_number' => 'NA/SL/003',
            'embedding' => array_fill(0, 128, 0.1),
        ])->assertForbidden();
    }

    public function test_verify_succeeds_when_enabled(): void
    {
        SystemSetting::setValue('face_auth_enabled', '1');
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        $soldier->update(['face_embedding' => array_fill(0, 128, 0.1)]);

        $this->postJson('/face/verify', [
            'service_number' => 'NA/SL/003',
            'embedding' => array_fill(0, 128, 0.1),
        ])->assertOk()->assertJsonPath('message', 'Face verified');

        $this->assertAuthenticatedAs($soldier->fresh());
    }

    public function test_admin_can_enroll_face_and_store_reference_image(): void
    {
        Storage::fake('local');
        $admin = User::where('email', 'admin@army.mil.ng')->first();
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        $this->actingAs($admin)->post('/face/enroll', [
            'user_id' => $soldier->id,
            'embedding' => array_fill(0, 128, 0.05),
            'image' => 'data:image/jpeg;base64,'.base64_encode('fake-bytes'),
        ])->assertRedirect();

        $soldier->refresh();
        $this->assertNotEmpty($soldier->face_embedding);
        $this->assertNotNull($soldier->face_enrolled_at);
        Storage::disk('local')->assertExists('faces/'.$soldier->id.'.jpg');
    }

    public function test_soldier_cannot_enroll_face(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        $target = User::where('email', 'admin@army.mil.ng')->first();

        $this->actingAs($soldier)->post('/face/enroll', [
            'user_id' => $target->id,
            'embedding' => array_fill(0, 128, 0.05),
        ])->assertForbidden();
    }
}
