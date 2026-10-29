<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Identity\GoogleHandoff;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request, GoogleHandoff $handoff)
    {
        $data = $request->validate(['code_challenge' => ['required', 'regex:/^[A-Za-z0-9_-]{43}$/D']]);
        $state = $handoff->begin($data['code_challenge']);

        // State is consumed explicitly and the resulting code is bound to browser proof.
        return Socialite::driver('google')->stateless()->with(['state' => $state])->redirect();
    }

    public function callback(Request $request, GoogleHandoff $handoff)
    {
        $data = $request->validate(['state' => 'required|string|size:64', 'code' => 'required|string|max:2048']);
        $challenge = $handoff->consumeState($data['state']);
        $google = Socialite::driver('google')->stateless()->user();
        $raw = $google->getRaw();
        $verified = ($raw['email_verified'] ?? $raw['verified_email'] ?? false) === true;
        $user = $handoff->user((string) $google->getEmail(), (string) $google->getName(), $verified);
        $code = $handoff->issue($user, $challenge);

        // A 60-second, one-use, proof-bound code; never a Sanctum bearer in a URL.
        return redirect(config('services.google.frontend_return').'?'.http_build_query(['code' => $code]))
            ->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    public function exchange(Request $request, GoogleHandoff $handoff)
    {
        $data = $request->validate([
            'code' => 'required|string|size:64',
            'code_verifier' => ['required', 'string', 'min:43', 'max:128', 'regex:/^[A-Za-z0-9._~-]+$/D'],
        ]);
        $user = $handoff->exchange($data['code'], $data['code_verifier']);

        return response()->json(['status' => true, 'user' => $user, 'token' => $user->createToken('api_token')->plainTextToken])
            ->withHeaders(['Cache-Control' => 'no-store']);
    }
}
