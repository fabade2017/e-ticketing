<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal AWS Rekognition client using Signature Version 4 signing.
 *
 * Only the CompareFaces action is implemented. It compares a live capture
 * (source) against an enrolled reference image (target) and returns the
 * highest similarity score as a 0-100 float, or null when the call fails.
 */
class AwsRekognitionService
{
    public function configured(): bool
    {
        return ! empty($this->key()) && ! empty($this->secret());
    }

    /**
     * @return float|null Similarity percentage (0-100) or null on failure.
     */
    public function compareFaces(string $sourceBytes, string $targetBytes, ?float $threshold = null): ?float
    {
        if (! $this->configured()) {
            return null;
        }

        $threshold ??= (float) config('face_auth.cloud.threshold', 80);

        $payload = [
            'SourceImage' => ['Bytes' => base64_encode($sourceBytes)],
            'TargetImage' => ['Bytes' => base64_encode($targetBytes)],
            'SimilarityThreshold' => $threshold,
        ];

        try {
            $response = $this->signedRequest('CompareFaces', $payload);
        } catch (\Throwable $e) {
            Log::warning('AWS Rekognition CompareFaces request failed: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('AWS Rekognition CompareFaces error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $matches = $response->json('FaceMatches') ?? [];

        if (empty($matches)) {
            return 0.0;
        }

        return (float) $matches[0]['Similarity'];
    }

    protected function signedRequest(string $action, array $payload): Response
    {
        $region = $this->region();
        $service = 'rekognition';
        $host = "{$service}.{$region}.amazonaws.com";
        $endpoint = "https://{$host}/";
        $contentType = 'application/x-amz-json-1.1';
        $target = "RekognitionService.{$action}";
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $payloadHash = hash('sha256', $body);

        $headers = [
            'content-type' => $contentType,
            'host' => $host,
            'x-amz-date' => $amzDate,
            'x-amz-target' => $target,
        ];

        $sessionToken = $this->sessionToken();
        if (! empty($sessionToken)) {
            $headers['x-amz-security-token'] = $sessionToken;
        }

        ksort($headers);

        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name.':'.$value."\n";
        }
        $signedHeaders = implode(';', array_keys($headers));

        $canonicalRequest = implode("\n", [
            'POST',
            '/',
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4'.$this->secret(), true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->key(),
            $credentialScope,
            $signedHeaders,
            $signature
        );

        $requestHeaders = [
            'Authorization' => $authorization,
            'Content-Type' => $contentType,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Target' => $target,
        ];

        if (! empty($sessionToken)) {
            $requestHeaders['X-Amz-Security-Token'] = $sessionToken;
        }

        return Http::withHeaders($requestHeaders)
            ->withBody($body, $contentType)
            ->timeout(15)
            ->post($endpoint);
    }

    protected function region(): string
    {
        return (string) config('face_auth.cloud.aws.region', 'us-east-1');
    }

    protected function key(): ?string
    {
        return config('face_auth.cloud.aws.key');
    }

    protected function secret(): ?string
    {
        return config('face_auth.cloud.aws.secret');
    }

    protected function sessionToken(): ?string
    {
        return config('face_auth.cloud.aws.session_token');
    }
}
