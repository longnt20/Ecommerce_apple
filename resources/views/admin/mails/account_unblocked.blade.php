@component('mail::message')
# Xin chào {{ $user->name }},

Tài khoản của bạn đã được **mở khóa** và có thể đăng nhập trở lại bình thường.

@component('mail::button', ['url' => config('app.url') . '/login'])
Đăng nhập ngay
@endcomponent

Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!  
Trân trọng,<br>
{{ config('app.name') }}
@endcomponent

