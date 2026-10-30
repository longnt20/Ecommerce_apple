<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>Coupon của bạn</title></head><body>
    <p>Chào {{ $user->name }},</p>
    <p>Bạn đã được cấp mã <strong>{{ $discount->code }}</strong>: {{ $discount->name }}.</p>
    <p>Giảm {{ $discount->discount_value }}{{ $discount->discount_type === 'percentage' ? '%' : ' VND' }} theo điều kiện của mã.</p>
    <p>Hạn sử dụng không muộn hơn {{ $discount->expire_date ?? 'ngày được ghi trong tài khoản' }}. Bạn có thể xem coupon trong trang tài khoản; mã được kiểm tra khi đặt hàng.</p>
</body></html>
