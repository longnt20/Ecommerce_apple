@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <h1>Coupon đã lưu trữ</h1>
    <table class="table"><thead><tr><th>Mã</th><th>Tên</th><th>Khôi phục</th></tr></thead><tbody>
        @foreach($coupons as $coupon)
        <tr><td>{{ $coupon->code }}</td><td>{{ $coupon->name }}</td><td>
            <form method="POST" action="{{ route('admin.coupons.restoreDelete', $coupon->id) }}">@csrf @method('PUT')<button class="btn btn-primary">Khôi phục</button></form>
        </td></tr>
        @endforeach
    </tbody></table>
    {{ $coupons->links() }}
</div>
@endsection
