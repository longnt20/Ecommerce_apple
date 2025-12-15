@extends('admin.layouts.app')

@section('title', 'Chi tiết đơn hàng')
@push('page-css')
    <style>
        .timeline {
            list-style: none;
            padding-left: 0;
            display: flex;
            gap: 30px;
            font-weight: 600;
        }

        .timeline li {
            position: relative;
            padding-left: 15px;
            color: #999;
        }

        .timeline li.active {
            color: #28a745;
        }

        .timeline li::before {
            content: "•";
            position: absolute;
            left: -5px;
            font-size: 22px;
        }
    </style>
@endpush
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Chi tiết Đơn hàng</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Danh sách đơn hàng</a></li>
                        <li class="breadcrumb-item active">Chi tiết đơn hàng</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">

        <div class="row">
            <div class="col-xl-9">
                <!-- ORDER ITEMS -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title flex-grow-1 mb-0">Đơn hàng: {{ $order->code }}</h5>
                            <div class="flex-shrink-0">
                                <a href="#" class="btn btn-success btn-sm">
                                    <i class="ri-download-2-fill align-middle me-1"></i> Invoice
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive table-card">
                            <table class="table table-nowrap align-middle table-borderless mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Giá</th>
                                        <th>Số lượng</th>
                                        <th class="text-end">Tổng tiền</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0 avatar-md bg-light rounded p-1">
                                                        <img src="{{ Storage::url($item->variant->thumbnail ?? null) }}"
                                                            class="img-fluid d-block">
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h5 class="fs-15 mb-1">{{ $item->product->name }}</h5>

                                                        @if ($item->variant)
                                                            <p class="text-muted mb-0">
                                                                Màu: <span
                                                                    class="fw-medium">{{ $item->variant->color_label }}</span>
                                                            </p>
                                                            <p class="text-muted mb-0">
                                                                Dung lượng: <span
                                                                    class="fw-medium">{{ $item->variant->storage }}</span>
                                                            </p>
                                                        @endif

                                                    </div>
                                                </div>
                                            </td>

                                            <td>{{ number_format($item->price, 0, ',', '.') }}đ</td>
                                            <td>{{ $item->quantity }}</td>

                                            <td class="text-end fw-medium">
                                                {{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ
                                            </td>
                                        </tr>
                                    @endforeach


                                    <!-- SUMMARY -->
                                    <tr class="border-top border-top-dashed">
                                        <td colspan="3"></td>
                                        <td colspan="1" class="p-0">
                                            <table class="table table-borderless mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td>Subtotal:</td>
                                                        <td class="text-end">
                                                            {{ number_format($order->subtotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td>Shipping:</td>
                                                        <td class="text-end">
                                                            {{ number_format($order->shipping_fee, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>

                                                    @if ($order->discount_amount > 0)
                                                        <tr>
                                                            <td>Discount:</td>
                                                            <td class="text-end text-danger">
                                                                -{{ number_format($order->discount_amount, 0, ',', '.') }}đ
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    <tr class="border-top border-top-dashed">
                                                        <th scope="row">Total:</th>
                                                        <th class="text-end">
                                                            {{ number_format($order->final_amount, 0, ',', '.') }}đ
                                                        </th>
                                                    </tr>

                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <!-- end card -->

                @php
                    $steps = [
                        'pending' => 'Đơn hàng mới',
                        'confirmed' => 'Đã xác nhận',
                        'shipping' => 'Đang giao',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy',
                    ];

                    // Xác định thứ tự để active
                    $statusOrder = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
                    $currentIndex = array_search($order->status, $statusOrder);
                    $statusIcons = [
                        'pending' => [
                            'src' => 'https://cdn.lordicon.com/medpcfcy.json',
                            'trigger' => 'loop',
                        ],
                        'confirmed' => [
                            'src' => 'https://cdn.lordicon.com/fsstjlds.json',
                            'trigger' => 'hover',
                        ],
                        'shipping' => [
                            'src' => 'https://cdn.lordicon.com/uetqnvvg.json',
                            'trigger' => 'loop',
                        ],
                        'completed' => [
                            'src' => 'https://cdn.lordicon.com/lupuorrc.json',
                            'trigger' => 'hover',
                        ],
                        'cancelled' => [
                            'src' => 'https://cdn.lordicon.com/jfhbogmw.json',
                            'trigger' => 'loop',
                        ],
                    ];

                @endphp
                <!-- ORDER STATUS (tạm để nguyên layout của bạn) -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Tiến độ đơn hàng</h5>
                        <div class="d-flex gap-2">
                            @foreach ($nextStatuses as $status)
                            <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $status }}">

                                <button class="btn {{ $status === 'cancelled' ? 'btn-danger' : 'btn-success' }}">
                                    {{ match ($status) {
                                        'confirmed' => 'Xác nhận',
                                        'shipping' => 'Chuyển sang vận chuyển',
                                        'completed' => 'Hoàn thành',
                                        'cancelled' => 'Hủy đơn',
                                    } }}
                                </button>
                            </form>
                        @endforeach
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="d-flex justify-content-between position-relative">

                            @foreach ($steps as $key => $label)
                                @php
                                    $active = in_array($key, $activeSteps);
                                    $isCancelledOrder = $order->status === 'cancelled';
                                    if ($isCancelledOrder) {
                                        $active = $key === 'cancelled';
                                    } else {
                                        $active = in_array($key, $activeSteps);
                                    }
                                    $isCancelledStep = $key === 'cancelled' && $isCancelledOrder;
                                    $icon = $statusIcons[$key] ?? null;
                                @endphp

                                <div class="text-center flex-fill">
                                    @if ($icon)
                                        <lord-icon src="{{ $icon['src'] }}"
                                            trigger="{{ $active || $isCancelledStep ? 'loop' : 'hover' }}"
                                            colors="primary:#405189,secondary:#0ab39c"
                                            style="width:50px;height:50px;opacity: {{ $active ? 1 : 0.4 }}">
                                        </lord-icon>
                                    @endif
                                    <div class="mb-2">
                                        <span
                                            class="badge rounded-pill
    {{ $isCancelledStep ? 'bg-danger' : ($active ? 'bg-success' : 'bg-secondary') }}">
                                            {{ $label }}
                                        </span>


                                    </div>

                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar
    {{ $isCancelledStep ? 'bg-danger' : ($active ? 'bg-success' : 'bg-secondary') }}"
                                            style="width: {{ $active ? '100%' : '0%' }};">
                                        </div>


                                    </div>

                                </div>
                            @endforeach

                        </div>

                    </div>
                </div>

            </div>

            <!-- RIGHT SIDEBAR -->
            <div class="col-xl-3">

                <!-- CUSTOMER -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin người đặt</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 vstack gap-3">
                            <li>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <img src="{{ Storage::url($order->user->avatar) }}" alt=""
                                            class="avatar-sm rounded">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fs-14 mb-1">{{ $order->user->name }}</h6>
                                    </div>
                                </div>
                            </li>
                            <li><i class="ri-mail-line me-2 align-middle text-muted fs-16"></i>{{ $order->user->email }}
                            </li>
                            <li><i
                                    class="ri-phone-line me-2 align-middle text-muted fs-16"></i>{{ $order->user->phone ?? 'không có' }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ADDRESS -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Địa chỉ giao hàng</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled vstack gap-2 fs-13 mb-0">
                            <li class="fw-medium fs-14">{{ $order->fullname }}</li>
                            <li>{{ $order->phone }}</li>
                            <li>{{ $order->address . ', ' . $order->ward . ', ' . $order->district . ', ' . $order->province }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- PAYMENT -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <p>Phương thức thanh toán: @if ($order->payment_method == 'COD')
                                <span class="badge bg-secondary">COD</span>
                            @else
                                <span class="badge bg-info">VNPAY</span>
                            @endif
                        </p>
                        <p>Trạng thái thanh toán: @if ($order->payment_status == 'unpaid')
                                <span class="badge bg-danger">Chưa thanh toán</span>
                            @else
                                <span class="badge bg-success">Đã thanh toán</span>
                            @endif
                        </p>
                        <p>Mã thanh toán: <strong>{{ $order->transaction_id ?? 'Không có' }}</strong></p>
                    </div>
                </div>

            </div>

        </div>
    </div>


@endsection
