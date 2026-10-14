<?php

namespace Tests\Feature;

use App\Models\BrandingLogo;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function superadmin(): User
    {
        return User::where('email', 'superadmin@army.mil.ng')->first();
    }

    public function test_guests_cannot_manage_branding(): void
    {
        $this->get('/admin/branding')->assertRedirect('/login');
    }

    public function test_non_superadmin_cannot_manage_branding(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        $this->actingAs($soldier)->get('/admin/branding')->assertForbidden();
        $this->actingAs($soldier)->post('/admin/branding', ['app_name' => 'x', 'app_motto' => 'y'])->assertForbidden();
    }

    public function test_superadmin_can_update_name_and_motto(): void
    {
        $this->actingAs($this->superadmin())
            ->post('/admin/branding', ['app_name' => 'Army E-Ticket', 'app_motto' => 'Semper Fidelis'])
            ->assertRedirect();

        $this->assertSame('Army E-Ticket', SystemSetting::getValue('app_name'));
        $this->assertSame('Semper Fidelis', SystemSetting::getValue('app_motto'));
    }

    public function test_download_url_is_configurable_and_shared(): void
    {
        $url = 'https://expo.dev/accounts/adetone007/projects/na-soldier-app/builds/newbuild-1234';

        $this->get('/download')->assertInertia(fn ($page) => $page
            ->where('branding.app_download_url', config('branding.download_url'))
        );

        $this->actingAs($this->superadmin())
            ->post('/admin/branding', [
                'app_name' => 'Army',
                'app_motto' => 'Motto',
                'app_download_url' => $url,
            ])
            ->assertRedirect();

        $this->assertSame($url, SystemSetting::getValue('app_download_url'));

        $this->get('/download')->assertInertia(fn ($page) => $page
            ->where('branding.app_download_url', $url)
        );
    }

    public function test_download_url_must_be_a_valid_url(): void
    {
        $this->actingAs($this->superadmin())
            ->post('/admin/branding', [
                'app_name' => 'Army',
                'app_motto' => 'Motto',
                'app_download_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('app_download_url');
    }

    public function test_logo_is_stored_in_database_and_served_publicly(): void
    {
        $this->actingAs($this->superadmin())
            ->post('/admin/branding', [
                'app_name' => 'Army',
                'app_motto' => 'Motto',
                'logo' => UploadedFile::fake()->image('crest.png', 40, 40),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('branding_logos', 1);
        $this->assertNotNull(BrandingLogo::url());

        $res = $this->get('/branding/logo');
        $res->assertOk();
        $this->assertStringContainsString('image', $res->headers->get('Content-Type'));

        $this->get('/admin/branding')->assertInertia(fn ($page) => $page
            ->where('branding.logo_url', fn ($url) => ! is_null($url))
        );
        $this->getJson('/api/branding')->assertOk()->assertJsonPath('app_logo', fn ($url) => ! is_null($url));
    }

    public function test_logo_validation_rejects_bad_type_and_oversize(): void
    {
        $this->actingAs($this->superadmin())
            ->post('/admin/branding', [
                'app_name' => 'Army',
                'app_motto' => 'Motto',
                'logo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('logo');

        $this->actingAs($this->superadmin())
            ->post('/admin/branding', [
                'app_name' => 'Army',
                'app_motto' => 'Motto',
                'logo' => UploadedFile::fake()->image('huge.png')->size(3000),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('branding_logos', 0);
    }

    public function test_logo_can_be_removed(): void
    {
        $this->actingAs($this->superadmin())->post('/admin/branding', [
            'app_name' => 'Army',
            'app_motto' => 'Motto',
            'logo' => UploadedFile::fake()->image('crest.png', 40, 40),
        ]);

        $this->assertDatabaseCount('branding_logos', 1);

        $this->actingAs($this->superadmin())->post('/admin/branding', [
            'app_name' => 'Army',
            'app_motto' => 'Motto',
            'remove_logo' => true,
        ])->assertRedirect();

        $this->assertDatabaseCount('branding_logos', 0);
        $this->get('/branding/logo')->assertNotFound();
    }
}
