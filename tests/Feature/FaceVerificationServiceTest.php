<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AwsRekognitionService;
use App\Services\FaceVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function configureAws(): void
    {
        config([
            'face_auth.cloud.aws.key' => 'AKIAEXAMPLE',
            'face_auth.cloud.aws.secret' => 'secret-example',
            'face_auth.cloud.aws.region' => 'us-east-1',
            'face_auth.cloud.threshold' => 80,
        ]);
    }

    public function test_aws_compare_faces_parses_similarity_and_signs_request(): void
    {
        $this->configureAws();
        Http::fake(['*' => Http::response(['FaceMatches' => [['Similarity' => 97.5]]], 200)]);

        $score = (new AwsRekognitionService)->compareFaces('source', 'target');

        $this->assertSame(97.5, $score);

        Http::assertSent(function ($request) {
            return str_starts_with($request->header('Authorization')[0] ?? '', 'AWS4-HMAC-SHA256')
                && $request->hasHeader('X-Amz-Target', 'RekognitionService.CompareFaces');
        });
    }

    public function test_aws_returns_null_when_not_configured(): void
    {
        config(['face_auth.cloud.aws.key' => null, 'face_auth.cloud.aws.secret' => null]);
        Http::fake();

        $this->assertNull((new AwsRekognitionService)->compareFaces('source', 'target'));
        Http::assertNothingSent();
    }

    public function test_aws_returns_zero_when_no_face_match(): void
    {
        $this->configureAws();
        Http::fake(['*' => Http::response(['FaceMatches' => []], 200)]);

        $this->assertSame(0.0, (new AwsRekognitionService)->compareFaces('source', 'target'));
    }

    public function test_local_fallback_when_no_reference_image(): void
    {
        Storage::fake('local');
        $this->configureAws();
        Http::fake();
        SystemSetting::setValue('face_auth_mode', 'cloud');

        $user = new User(['face_embedding' => array_fill(0, 128, 0.1)]);
        $user->id = 5;

        $result = app(FaceVerificationService::class)->verify($user, array_fill(0, 128, 0.1), 'live-bytes');

        $this->assertFalse($result['cloud_used']);
        $this->assertTrue($result['matched']);
        Http::assertNothingSent();
    }

    public function test_cloud_is_used_when_reference_image_and_image_present(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('faces/5.jpg', 'reference-bytes');
        $this->configureAws();
        Http::fake(['*' => Http::response(['FaceMatches' => [['Similarity' => 92]]], 200)]);
        SystemSetting::setValue('face_auth_mode', 'cloud');
        SystemSetting::setValue('face_cloud_provider', 'aws');

        $user = new User(['face_embedding' => array_fill(0, 128, 0.1)]);
        $user->id = 5;

        $result = app(FaceVerificationService::class)->verify($user, array_fill(0, 128, 0.1), 'live-bytes');

        $this->assertTrue($result['cloud_used']);
        $this->assertTrue($result['matched']);
        $this->assertSame(0.92, $result['similarity']);
        Http::assertSentCount(1);
    }
}
