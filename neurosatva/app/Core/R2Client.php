<?php

declare(strict_types=1);

class R2Client
{
    public static function isConfigured(): bool
    {
        $accountId = (string) (app_config('r2_account_id') ?? '');
        $accessKey = (string) (app_config('r2_access_key_id') ?? '');
        $secretKey = (string) (app_config('r2_secret_access_key') ?? '');
        $bucket = (string) (app_config('r2_bucket') ?? '');

        return $accountId !== '' && $accessKey !== '' && $secretKey !== '' && $bucket !== '';
    }

    /**
     * Generate an S3 SigV4 Presigned PUT URL for Cloudflare R2
     *
     * @param string $key Object key / path in bucket (e.g. 'sensory_mod_01/main.mp4')
     * @param int $expiresIn Expiration in seconds (default 3600 = 1 hour)
     * @return string
     */
    public static function generatePresignedPutUrl(string $key, int $expiresIn = 3600): string
    {
        $accountId = trim((string) app_config('r2_account_id'));
        $accessKey = trim((string) app_config('r2_access_key_id'));
        $secretKey = trim((string) app_config('r2_secret_access_key'));
        $bucket = trim((string) (app_config('r2_bucket') ?: 'modules'));

        if ($accountId === '' || $accessKey === '' || $secretKey === '' || $bucket === '') {
            throw new RuntimeException('Cloudflare R2 is not fully configured.');
        }

        $cleanKey = ltrim($key, '/');
        $host = "{$accountId}.r2.cloudflarestorage.com";
        $region = 'auto';
        $service = 's3';

        // RFC 3986 encoding for path segments
        $pathParts = explode('/', "{$bucket}/{$cleanKey}");
        $encodedParts = array_map('rawurlencode', $pathParts);
        $canonicalUri = '/' . implode('/', $encodedParts);

        $now = time();
        $dateIso = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        // Query parameters for presigned URL (must be sorted alphabetically)
        $queryParams = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => "{$accessKey}/{$credentialScope}",
            'X-Amz-Date' => $dateIso,
            'X-Amz-Expires' => (string) $expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($queryParams);

        $queryStringParts = [];
        foreach ($queryParams as $k => $v) {
            $queryStringParts[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $canonicalQueryString = implode('&', $queryStringParts);

        $canonicalHeaders = "host:{$host}\n";
        $signedHeaders = 'host';
        $payloadHash = 'UNSIGNED-PAYLOAD';

        $canonicalRequest = "PUT\n"
            . $canonicalUri . "\n"
            . $canonicalQueryString . "\n"
            . $canonicalHeaders . "\n"
            . $signedHeaders . "\n"
            . $payloadHash;

        $stringToSign = "AWS4-HMAC-SHA256\n"
            . $dateIso . "\n"
            . $credentialScope . "\n"
            . hash('sha256', $canonicalRequest);

        // Derive AWS SigV4 signing key
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return "https://{$host}{$canonicalUri}?{$canonicalQueryString}&X-Amz-Signature={$signature}";
    }

    /**
     * Get the public URL for an object stored in R2
     */
    public static function getPublicUrl(string $key): string
    {
        $publicUrl = rtrim((string) (app_config('r2_public_url') ?? ''), '/');
        $cleanKey = ltrim($key, '/');
        
        $pathParts = explode('/', $cleanKey);
        $encodedParts = array_map('rawurlencode', $pathParts);
        $encodedKey = implode('/', $encodedParts);

        if ($publicUrl !== '') {
            return "{$publicUrl}/{$encodedKey}";
        }

        // If no custom/dev public URL is set, return the standard R2 dev or direct path
        $accountId = (string) app_config('r2_account_id');
        $bucket = (string) (app_config('r2_bucket') ?: 'modules');
        return "https://{$accountId}.r2.cloudflarestorage.com/{$bucket}/{$encodedKey}";
    }
}
