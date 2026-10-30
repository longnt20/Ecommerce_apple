@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Cài đặt kho</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.inventory.settings.update') }}">
        @csrf
        <label for="low_stock_threshold">Ngưỡng cảnh báo tồn tổng quát trong báo cáo</label>
        <input class="form-control" id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" max="2147483647" required value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}">
        <p>Ngưỡng riêng của từng SKU vẫn dùng mức tồn tối thiểu của SKU. Đơn hàng luôn giữ tồn và không cho phép tồn âm.</p>
        <input type="hidden" name="auto_reserve" value="1">
        <input type="hidden" name="negative_stock_allowed" value="0">
        <button class="btn btn-primary" type="submit">Lưu cài đặt</button>
    </form>
</div>
@endsection
