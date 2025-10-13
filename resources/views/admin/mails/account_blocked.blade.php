@component('mail::message')
# Xin chào {{ $user->name }},

Tài khoản của bạn đã bị **khóa tạm thời** bởi quản trị viên.

Nếu bạn cho rằng đây là sự nhầm lẫn, vui lòng liên hệ với bộ phận hỗ trợ để được trợ giúp.

@component('mail::button', ['url' => config('app.url')])
Truy cập trang chủ
@endcomponent

Cảm ơn bạn,<br>
{{ config('app.name') }}
@endcomponent

