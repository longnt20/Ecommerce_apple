<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GoogleAuthController extends Controller
{
    // Bước 1: Redirect user đến Google
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Bước 2: Google callback về
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Tìm hoặc tạo user mới
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => Hash::make(uniqid()), // random password
                ]
            );

            // Tạo Sanctum token
            $token = $user->createToken('api_token')->plainTextToken;

            return redirect("/login-success?token=$token");
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Đăng nhập Google thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
