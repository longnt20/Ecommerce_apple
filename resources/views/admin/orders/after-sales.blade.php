@extends('admin.layouts.app')
@section('title', 'Đối soát tiền và hàng trả')
@section('content')
<div class="container-fluid">
    <h1>Đối soát đơn {{ $order->code }}</h1>
    <p>Đơn: {{ $order->status }} · Tiền: {{ $order->payment_status }} · Giá trị sau giảm: {{ number_format($order->final_amount) }}đ</p>
    <p>Hoàn tiền được đối soát thủ công. Nhận hàng trả và hoàn tiền là hai thao tác độc lập.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if(strtolower($order->payment_method) === 'cod' && $order->payment_status === 'unpaid' && in_array($order->status, ['shipping', 'completed']))
    <div class="card"><div class="card-body">
        <h2 class="h5">Ghi nhận đã thu đủ COD</h2>
        <form method="POST" action="{{ route('admin.orders.money', $order->id) }}">
            @csrf
            <input type="hidden" name="operation_key" value="{{ Str::uuid() }}">
            <input type="hidden" name="action" value="collect_cod">
            <input type="hidden" name="amount" value="{{ (int) $order->final_amount }}">
            <label>Mã đối soát của đơn vị vận chuyển / sao kê</label>
            <input class="form-control" name="reference" required maxlength="128">
            <button class="btn btn-primary mt-2">Xác nhận đã thu {{ number_format($order->final_amount) }}đ</button>
        </form>
    </div></div>
    @endif

    @if($order->payment_status === 'paid')
    <div class="card"><div class="card-body">
        <h2 class="h5">Yêu cầu hoàn tiền</h2>
        <form method="POST" action="{{ route('admin.orders.money', $order->id) }}">
            @csrf
            <input type="hidden" name="operation_key" value="{{ Str::uuid() }}">
            <input type="hidden" name="action" value="request_refund">
            <label>Số tiền hoàn (VND)</label><input class="form-control" type="number" name="amount" min="1" max="{{ (int) $order->final_amount }}" required>
            <label>Lý do</label><input class="form-control" name="reason" required maxlength="500">
            <button class="btn btn-primary mt-2">Ghi yêu cầu hoàn</button>
        </form>
    </div></div>
    @endif

    <h2 class="h5">Lịch sử tiền</h2>
    <table class="table"><thead><tr><th>Mã</th><th>Loại</th><th>Trạng thái</th><th>Số tiền</th><th>Đối soát</th></tr></thead><tbody>
    @foreach($history['payments'] as $payment)
        <tr><td>{{ $payment['id'] }}</td><td>{{ $payment['kind'] }}</td><td>{{ $payment['status'] }}</td><td>{{ number_format($payment['amount']) }}đ</td><td>{{ $payment['reference'] }}</td></tr>
        @if($payment['kind'] === 'refund' && $payment['status'] === 'requested')
        <tr><td colspan="5">
            <form method="POST" action="{{ route('admin.orders.money', $order->id) }}">
                @csrf
                <input type="hidden" name="operation_key" value="{{ Str::uuid() }}">
                <input type="hidden" name="refund_id" value="{{ $payment['id'] }}">
                <label>Kết quả</label><select name="action" class="form-select"><option value="settle_refund">Đã hoàn qua đối soát</option><option value="reject_refund">Từ chối yêu cầu</option></select>
                <label>Mã giao dịch hoàn (bắt buộc khi đã hoàn)</label><input class="form-control" name="reference" maxlength="128">
                <label>Lý do / bằng chứng đối soát</label><input class="form-control" name="reason" maxlength="500" required>
                <button class="btn btn-warning mt-2">Ghi nhận kết quả thủ công</button>
            </form>
        </td></tr>
        @endif
    @endforeach
    </tbody></table>

    @if(in_array($order->status, ['shipping', 'completed']))
    <div class="card"><div class="card-body">
        <h2 class="h5">Nhận hàng trả sau kiểm tra</h2>
        <form method="POST" action="{{ route('admin.orders.returns', $order->id) }}">
            @csrf
            <input type="hidden" name="operation_key" value="{{ Str::uuid() }}">
            <label>Sản phẩm trên đơn</label><select class="form-select" name="order_item_id">
                @foreach($order->items as $item)<option value="{{ $item->id }}">{{ $item->product_name }} · {{ $item->variant_name }}</option>@endforeach
            </select>
            <label>Số lượng thực nhận</label><input class="form-control" name="quantity" type="number" min="1" max="10000" required>
            <label>Kết quả kiểm tra</label><select class="form-select" name="disposition"><option value="damaged">Cách ly, không thể bán</option><option value="sellable">Kiểm tra đạt, cho phép bán lại</option></select>
            <label>Mã biên nhận</label><input class="form-control" name="reference" required maxlength="128">
            <label>Kết quả kiểm tra chi tiết</label><input class="form-control" name="reason" required maxlength="500">
            <button class="btn btn-primary mt-2">Ghi nhận hàng trả</button>
        </form>
    </div></div>
    @endif
    <h2 class="h5">Biên nhận hàng trả</h2>
    <table class="table"><thead><tr><th>Mã biên nhận</th><th>Dòng đơn</th><th>Số lượng</th><th>Kiểm tra</th><th>Lý do</th></tr></thead><tbody>
        @foreach($history['returns'] as $receipt)<tr><td>{{ $receipt['reference'] }}</td><td>{{ $receipt['order_item_id'] }}</td><td>{{ $receipt['quantity'] }}</td><td>{{ $receipt['disposition'] }}</td><td>{{ $receipt['reason'] }}</td></tr>@endforeach
    </tbody></table>
</div>
@endsection
