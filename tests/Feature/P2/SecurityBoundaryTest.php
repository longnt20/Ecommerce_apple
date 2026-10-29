<?php

namespace Tests\Feature\P2;

use App\Services\Identity\GoogleHandoff;
use App\Services\Payments\VnPayGateway;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityBoundaryTest extends TestCase
{
    public function test_vnpay_php_reference_canonicalization_and_signature(): void
    {
        config(['vnpay.vnp_hash_secret' => 'synthetic-test-secret']);
        $gateway = new VnPayGateway;
        $data = ['vnp_TxnRef' => 'test-1', 'vnp_OrderInfo' => 'Thanh toan: A+B / điện thoại', 'vnp_Amount' => '10000'];
        $canonical = 'vnp_Amount=10000&vnp_OrderInfo=Thanh+toan%3A+A%2BB+%2F+%C4%91i%E1%BB%87n+tho%E1%BA%A1i&vnp_TxnRef=test-1';
        $this->assertSame($canonical, $gateway->canonical($data));
        $data['vnp_SecureHash'] = hash_hmac('sha512', $canonical, 'synthetic-test-secret');
        $this->assertTrue($gateway->verified($data));
        $data['vnp_Amount'] = '0';
        $this->assertFalse($gateway->verified($data));
        $data['vnp_Amount'] = ['10000'];
        $this->assertFalse($gateway->verified($data));
    }

    public function test_invalid_state_and_url_bearers_are_not_accepted(): void
    {
        $this->getJson('/api/auth/google/redirect')->assertUnprocessable();
        $this->getJson('/api/auth/google/callback?code=synthetic&state='.str_repeat('a', 64))->assertUnprocessable();
        $this->getJson('/api/user?token=synthetic')->assertUnauthorized();
    }

    public function test_state_is_one_use_and_expires(): void
    {
        $handoff = app(GoogleHandoff::class);
        $challenge = str_repeat('a', 43);
        $state = $handoff->begin($challenge);
        $this->assertSame($challenge, $handoff->consumeState($state));
        $this->getJson('/api/auth/google/callback?code=synthetic&state='.$state)->assertUnprocessable();
        $state = $handoff->begin($challenge);
        $this->travel(301)->seconds();
        $this->getJson('/api/auth/google/callback?code=synthetic&state='.$state)->assertUnprocessable();
    }

    public function test_errors_are_redacted_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        Route::get('/', fn () => throw new \RuntimeException('password=synthetic-private-secret'));
        $response = $this->getJson('/')->assertStatus(500)
            ->assertJsonStructure(['message', 'request_id'])->assertJsonMissingPath('trace')->assertJsonMissingPath('exception');
        $this->assertStringNotContainsString('synthetic-private-secret', $response->getContent());
        $this->assertSame($response->json('request_id'), $response->headers->get('X-Request-ID'));
    }
}
