<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client minimal untuk FCM HTTP v1 — tanpa library pihak ketiga.
 *
 * Alur standar Google service account:
 *   1. Tanda tangani JWT (RS256) dengan private key service account
 *   2. Tukar JWT itu menjadi access token OAuth (berlaku 1 jam, di-cache)
 *   3. POST pesan ke /v1/projects/{project_id}/messages:send
 *
 * Sengaja tidak memakai kreait/firebase-php: library itu butuh lcobucci/jwt >= 4.3,
 * sedangkan project ini terkunci di 4.0.4 oleh tymon/jwt-auth.
 */
class FcmClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** Token Google berlaku 3600 detik; buang sedikit lebih awal supaya tidak kedaluwarsa di tengah request. */
    private const TOKEN_CACHE_SECONDS = 3300;

    private ?array $credentials = null;

    public function __construct(
        private string $credentialsPath,
        private int $timeoutSeconds = 5,
    ) {
    }

    /**
     * @param  array  $message  Isi field "message" FCM v1 (topic, notification, data, android, ...)
     *
     * @throws \Illuminate\Http\Client\RequestException  kalau Google menolak / tidak terjangkau
     * @throws RuntimeException  kalau file kredensial bermasalah
     */
    public function send(array $message): void
    {
        $credentials = $this->credentials();

        $response = Http::withToken($this->accessToken($credentials))
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send",
                ['message' => $message]
            );

        if ($response->status() === 401) {
            // Token ditolak (mis. key di-rotate) — jangan dipakai lagi 55 menit ke depan
            Cache::forget($this->tokenCacheKey($credentials));
        }

        $response->throw();
    }

    private function accessToken(array $credentials): string
    {
        return Cache::remember(
            $this->tokenCacheKey($credentials),
            self::TOKEN_CACHE_SECONDS,
            function () use ($credentials) {
                $token = Http::asForm()
                    ->timeout($this->timeoutSeconds)
                    ->post($credentials['token_uri'], [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $this->signedJwt($credentials),
                    ])
                    ->throw()
                    ->json('access_token');

                if (! is_string($token) || $token === '') {
                    throw new RuntimeException('Google OAuth tidak mengembalikan access_token.');
                }

                return $token;
            }
        );
    }

    private function signedJwt(array $credentials): string
    {
        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $credentials['private_key_id']];
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $unsigned = $this->base64Url(json_encode($header)) . '.' . $this->base64Url(json_encode($claims, JSON_UNESCAPED_SLASHES));

        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException("private_key di {$this->credentialsPath} tidak valid.");
        }

        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Gagal menandatangani JWT untuk FCM.');
        }

        return $unsigned . '.' . $this->base64Url($signature);
    }

    private function credentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = $this->credentialsPath;
        $isAbsolute = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
        $fullPath = $isAbsolute ? $path : base_path($path);

        if (! is_file($fullPath)) {
            throw new RuntimeException("File kredensial Firebase tidak ditemukan: {$path}");
        }

        $credentials = json_decode((string) file_get_contents($fullPath), true);

        foreach (['project_id', 'private_key_id', 'private_key', 'client_email', 'token_uri'] as $key) {
            if (empty($credentials[$key])) {
                throw new RuntimeException("File kredensial Firebase {$path} tidak punya field '{$key}'.");
            }
        }

        return $this->credentials = $credentials;
    }

    /** Key ikut private_key_id supaya rotate kredensial otomatis memakai token baru. */
    private function tokenCacheKey(array $credentials): string
    {
        return 'fcm-access-token:' . $credentials['private_key_id'];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
