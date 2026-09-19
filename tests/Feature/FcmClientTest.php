<?php

namespace Tests\Feature;

use App\Services\Push\FcmClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmClientTest extends TestCase
{
    private const FIXTURE = 'tests/Fixtures/fcm-test-service-account.json';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    private function fakeGoogle(int $fcmStatus = 200): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-access-token', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(
                $fcmStatus === 200 ? ['name' => 'projects/kafe-dilangit-test/messages/1'] : ['error' => ['status' => 'UNAVAILABLE']],
                $fcmStatus
            ),
        ]);
    }

    private function client(): FcmClient
    {
        return new FcmClient(self::FIXTURE, 5);
    }

    public function test_sends_message_to_project_endpoint_with_bearer_token(): void
    {
        $this->fakeGoogle();

        $this->client()->send(['topic' => 'kitchen-orders-test', 'data' => ['type' => 'x']]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/kafe-dilangit-test/messages:send'
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request['message']['topic'] === 'kitchen-orders-test'
                && $request['message']['data']['type'] === 'x';
        });
    }

    public function test_token_request_carries_a_valid_signed_jwt(): void
    {
        $this->fakeGoogle();

        $this->client()->send(['topic' => 't']);

        $tokenRequest = Http::recorded(fn (Request $r) => str_contains($r->url(), 'oauth2.googleapis.com'))->first()[0];
        $this->assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $tokenRequest['grant_type']);

        [$header, $claims, $signature] = explode('.', $tokenRequest['assertion']);
        $decode = fn (string $part) => base64_decode(strtr($part, '-_', '+/'));

        $this->assertSame(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'test-key-id'], json_decode($decode($header), true));

        $claimsArray = json_decode($decode($claims), true);
        $this->assertSame('fcm-test@kafe-dilangit-test.iam.gserviceaccount.com', $claimsArray['iss']);
        $this->assertSame('https://www.googleapis.com/auth/firebase.messaging', $claimsArray['scope']);
        $this->assertSame('https://oauth2.googleapis.com/token', $claimsArray['aud']);
        $this->assertSame(3600, $claimsArray['exp'] - $claimsArray['iat']);

        // Tanda tangan harus bisa diverifikasi dengan public key pasangan fixture
        $fixture = json_decode(file_get_contents(base_path(self::FIXTURE)), true);
        $publicKey = openssl_pkey_get_details(openssl_pkey_get_private($fixture['private_key']))['key'];
        $this->assertSame(1, openssl_verify("{$header}.{$claims}", $decode($signature), $publicKey, OPENSSL_ALGO_SHA256));
    }

    public function test_access_token_is_cached_between_sends(): void
    {
        $this->fakeGoogle();
        $client = $this->client();

        $client->send(['topic' => 't']);
        $client->send(['topic' => 't']);

        $tokenRequests = Http::recorded(fn (Request $r) => str_contains($r->url(), 'oauth2.googleapis.com'));
        $this->assertCount(1, $tokenRequests, 'Token OAuth harus dipakai ulang, bukan diminta tiap push');
    }

    public function test_fcm_error_throws_so_the_dispatcher_can_log_it(): void
    {
        $this->fakeGoogle(503);

        $this->expectException(RequestException::class);

        $this->client()->send(['topic' => 't']);
    }

    public function test_rejected_token_is_forgotten_so_next_send_fetches_a_fresh_one(): void
    {
        $this->fakeGoogle(401);
        $client = $this->client();

        try {
            $client->send(['topic' => 't']);
            $this->fail('401 dari FCM harus melempar exception');
        } catch (RequestException) {
        }

        try {
            $client->send(['topic' => 't']);
        } catch (RequestException) {
        }

        $tokenRequests = Http::recorded(fn (Request $r) => str_contains($r->url(), 'oauth2.googleapis.com'));
        $this->assertCount(2, $tokenRequests);
    }

    public function test_missing_credentials_file_gives_a_clear_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('storage/app/firebase/does-not-exist.json');

        (new FcmClient('storage/app/firebase/does-not-exist.json', 5))->send(['topic' => 't']);
    }
}
