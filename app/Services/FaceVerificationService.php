<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Orchestrates face verification across the available backends.
 *
 * - local: 128-float embedding cosine similarity (always available).
 * - cloud: AWS Rekognition CompareFaces, used only when the client sends a
 *   still image AND credentials are configured AND a reference image was
 *   stored at enrollment. Otherwise it transparently falls back to local.
 */
class FaceVerificationService
{
    public function __construct(protected AwsRekognitionService $rekognition) {}

    public function mode(): string
    {
        return (string) SystemSetting::getValue('face_auth_mode', config('face_auth.mode', 'local'));
    }

    public function provider(): string
    {
        return (string) SystemSetting::getValue('face_cloud_provider', config('face_auth.cloud.provider', 'aws'));
    }

    /**
     * @return array{mode:string, provider:string, similarity:float, matched:bool, cloud_used:bool}
     */
    public function verify(User $user, array $embedding, ?string $imageBytes = null): array
    {
        $mode = $this->mode();
        $provider = $this->provider();
        $localThreshold = (float) config('face_auth.threshold', 0.6);
        $localSimilarity = $this->cosineSimilarity((array) $user->face_embedding, $embedding);

        if ($mode === 'cloud' && $provider === 'aws' && $imageBytes !== null && $this->rekognition->configured()) {
            $reference = $this->referenceImage($user);

            if ($reference !== null) {
                $cloudThreshold = (float) config('face_auth.cloud.threshold', 80);
                $score = $this->rekognition->compareFaces($imageBytes, $reference, $cloudThreshold);

                if ($score !== null) {
                    return [
                        'mode' => $mode,
                        'provider' => $provider,
                        'similarity' => round($score / 100, 4),
                        'matched' => $score >= $cloudThreshold,
                        'cloud_used' => true,
                    ];
                }
            }
        }

        return [
            'mode' => $mode,
            'provider' => $provider,
            'similarity' => $localSimilarity,
            'matched' => $localSimilarity >= $localThreshold,
            'cloud_used' => false,
        ];
    }

    public function storeReferenceImage(User $user, string $bytes): void
    {
        Storage::disk('local')->put($this->referencePath($user), $bytes);
    }

    public function referenceImage(User $user): ?string
    {
        $path = $this->referencePath($user);

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($path);

        return $contents === '' ? null : $contents;
    }

    public function hasReferenceImage(User $user): bool
    {
        return Storage::disk('local')->exists($this->referencePath($user));
    }

    protected function referencePath(User $user): string
    {
        return 'faces/'.$user->getKey().'.jpg';
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $length = min(count($a), count($b));
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        $den = sqrt($normA) * sqrt($normB);

        return $den == 0 ? 0.0 : $dot / $den;
    }
}
