<?php

namespace App\Services\Identity;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleHandoff
{
    public function begin(string $challenge): string
    {
        $state = Str::random(64);
        Cache::put('google-state:'.hash('sha256', $state), $challenge, 300);

        return $state;
    }

    public function consumeState(string $state): string
    {
        return $this->consume('google-state:'.hash('sha256', $state), fn ($value) => is_string($value));
    }

    public function user(string $email, string $name, bool $verified): User
    {
        abort_unless($verified && filter_var($email, FILTER_VALIDATE_EMAIL), 403, 'Google chưa xác minh email.');
        $user = User::where('email', $email)->first();
        // No automatic linking of unverified local accounts (pre-registration takeover).
        abort_if($user && ! $user->email_verified_at, 409, 'Cần xác minh tài khoản hiện có trước khi liên kết Google.');
        if (! $user) {
            $user = User::create([
                'email' => $email, 'name' => $name, 'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(), 'status' => 'active',
            ]);
        }
        abort_unless($user->status === 'active', 403, 'Tài khoản không hoạt động.');

        return $user;
    }

    public function issue(User $user, string $challenge): string
    {
        $code = Str::random(64);
        Cache::put('google-code:'.hash('sha256', $code), ['user_id' => $user->id, 'challenge' => $challenge], 60);

        return $code;
    }

    public function exchange(string $code, string $verifier): User
    {
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $grant = $this->consume('google-code:'.hash('sha256', $code), fn ($value) => is_array($value) && hash_equals($value['challenge'], $challenge));
        $user = User::findOrFail($grant['user_id']);
        abort_unless($user->status === 'active', 403, 'Tài khoản không hoạt động.');

        return $user;
    }

    private function consume(string $key, callable $valid): mixed
    {
        // Cache::pull alone is get+delete, not atomic. Use the existing cache lock.
        return Cache::lock($key.':lock', 10)->block(3, function () use ($key, $valid) {
            $value = Cache::get($key);
            if ($value === null || ! $valid($value)) {
                throw ValidationException::withMessages(['code' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn.']);
            }
            Cache::forget($key);

            return $value;
        });
    }
}
