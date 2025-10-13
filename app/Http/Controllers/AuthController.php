<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm(){
        return view('auth.admin.login');
    }
    public function login(Request $request){
        $credentials = $request->only('email','password');
        if (Auth::attempt($credentials)) {
            if (Auth::user()->role !== 'admin') {
                Auth::logout();
                return back()->with('error', 'Bạn không phải admin');
            }
            if (Auth::user()->status === 'blocked') {
                Auth::logout();
                return back()->with('error', 'Tài khoản của bạn đã bị khóa');
            }
            return redirect()->route('admin.dashboard')->with('success','Đăng nhập thành công');
        }
        return back()->with('error','Email hoặc mật khẩu không đúng!!!');
    }
    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
