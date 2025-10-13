@component('mail::message')
# Xin chào {{ $user->name }},

Vai trò của bạn trong hệ thống đã được cập nhật:

- **Trước đây:** {{ $oldRole }}
- **Hiện tại:** {{ $newRole }}

Vui lòng đăng nhập lại để hệ thống cập nhật quyền truy cập của bạn.

@component('mail::button', ['url' => config('app.url')])
Trang chủ
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent

