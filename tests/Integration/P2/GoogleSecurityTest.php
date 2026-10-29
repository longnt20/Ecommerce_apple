<?php

namespace Tests\Integration\P2;

use App\Models\User;
use App\Services\Identity\GoogleHandoff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\Support\MySqlTestCase;

class GoogleSecurityTest extends MySqlTestCase
{
    private function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function test_verified_existing_user_keeps_password_and_code_is_proof_bound_and_one_use(): void
    {
        $this->seedCommerce();
        $user = User::findOrFail(910001);
        $user->update(['email_verified_at' => now(), 'password' => Hash::make('Synthetic-existing-password')]);
        $password = $user->password;
        $handoff = app(GoogleHandoff::class);
        $verifier = str_repeat('a', 43);
        $state = $handoff->begin($this->challenge($verifier));
        $google = (new GoogleUser)->setRaw(['email_verified' => true])->map(['id' => 'google-test-sub', 'name' => 'Google test', 'email' => $user->email]);
        $provider = \Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($google);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $response = $this->get('/api/auth/google/callback?state='.$state.'&code=synthetic-provider-code')->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringNotContainsString('token=', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame($password, $user->fresh()->password);
        $this->postJson('/api/auth/google/exchange', ['code' => $query['code'], 'code_verifier' => str_repeat('b', 43)])->assertUnprocessable();
        $this->postJson('/api/auth/google/exchange', ['code' => $query['code'], 'code_verifier' => $verifier])->assertOk()->assertJsonStructure(['token', 'user']);
        $this->postJson('/api/auth/google/exchange', ['code' => $query['code'], 'code_verifier' => $verifier])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_expired_handoff_and_blocked_account_cannot_receive_a_token(): void
    {
        $this->seedCommerce();
        $handoff = app(GoogleHandoff::class);
        $verifier = str_repeat('a', 43);
        $user = User::findOrFail(910001);
        $code = $handoff->issue($user, $this->challenge($verifier));
        $this->travel(61)->seconds();
        $this->postJson('/api/auth/google/exchange', ['code' => $code, 'code_verifier' => $verifier])->assertUnprocessable();
        $code = $handoff->issue($user, $this->challenge($verifier));
        $user->update(['status' => 'blocked']);
        $this->postJson('/api/auth/google/exchange', ['code' => $code, 'code_verifier' => $verifier])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unverified_existing_local_account_is_not_silently_linked(): void
    {
        $this->seedCommerce();
        $user = User::findOrFail(910001);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            app(GoogleHandoff::class)->user($user->email, 'Synthetic Google user', true);
        } finally {
            $this->assertSame($user->password, $user->fresh()->password);
            $this->assertDatabaseCount('personal_access_tokens', 0);
        }
    }

    public function test_new_google_user_is_created_without_overwriting_an_existing_identity(): void
    {
        $user = app(GoogleHandoff::class)->user('new-google@example.test', 'Synthetic Google user', true);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, DB::table('users')->where('email', $user->email)->count());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
