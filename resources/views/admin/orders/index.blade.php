@extends('admin.layouts.app')

@push('page-css')
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 150px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
    </style>
@endpush

@section('title')
    Danh sách đơn hàng
@endsection

@section('content')
    {{-- THỐNG KÊ --}}
    <div class="row cursor-pointer">
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card total-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-primary">
                        <i class="la la-shopping-cart"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Tổng đơn hàng</h5>
                    <h3 class="card-text fw-bold">{{ $orders_total ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card approved-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-info">
                        <i class="la la-history"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Đơn chờ xác nhận</h5>
                    <h3 class="card-text fw-bold text-info">{{ $orders_pending ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card pending-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-warning">
                        <i class="la la-truck"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Đơn đang giao</h5>
                    <h3 class="card-text fw-bold text-warning">{{ $orders_shipping ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card rejected-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-success">
                        <i class="la la-check-circle"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Đơn đã giao</h5>
                    <h3 class="card-text fw-bold text-success">{{ $orders_delivered ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- NỘI DUNG CHÍNH --}}
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Quản lí đơn hàng</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Danh sách đơn hàng</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="card">

                    {{-- Bộ lọc --}}
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title mb-0">Danh sách đơn hàng</h4>
                        <button class="btn btn-outline-primary btn-sm" id="toggleFilterBtn">
                            <i class="ri-filter-3-line"></i> Bộ lọc
                        </button>
                    </div>

                    <div class="card-body" id="filterForm" style="display: none;">
                        <form action="{{ route('admin.orders.index') }}" method="GET">

                            <div class="row g-3">

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mã đơn</label>
                                    <input type="text" name="code" class="form-control" value="{{ request('code') }}"
                                        placeholder="VD: DH202501-12345">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control" value="{{ request('phone') }}"
                                        placeholder="Nhập SĐT khách hàng">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Trạng thái</label>
                                    <select name="status" class="form-select">
                                        <option value="">-- Tất cả --</option>
                                        <option value="pending">Chờ xác nhận</option>
                                        <option value="confirmed">Đã xác nhận</option>
                                        <option value="shipping">Đang giao</option>
                                        <option value="delivered">Đã giao</option>
                                        <option value="cancelled">Đã hủy</option>
                                    </select>
                                </div>

                                <div class="col-md-12 d-flex justify-content-end gap-2 mt-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-search-line"></i> Lọc
                                    </button>
                                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i> Đặt lại
                                    </a>
                                </div>

                            </div>
                        </form>
                    </div>

                    {{-- TABLE --}}
                    <div class="card-body">
                        <div class="table-responsive table-card mt-3">
                            <table class="table align-middle table-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Phương thức TT</th>
                                        <th>Trạng Thái TT</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($orders as $order)
                                        <tr>

                                            <td>{{ $loop->iteration }}</td>

                                            <td class="fw-bold text-primary">
                                                {{ $order->code }}
                                            </td>

                                            <td>{{ $order->fullname }} <br> {{ $order->phone }} <br> {{ $order->email }}</td>


                                            <td>{{ number_format($order->final_amount, 0, ',', '.') }}đ</td>

                                            <td>
                                                @if ($order->payment_method == 'COD')
                                                    <span class="badge bg-secondary">COD</span>
                                                @else
                                                    <span class="badge bg-info">VNPAY</span>
                                                @endif
                                            </td>
                                             <td>
                                                @if ($order->payment_status == 'unpaid')
                                                    <span class="badge bg-danger">Chưa thanh toán</span>
                                                @else
                                                    <span class="badge bg-success">Đã thanh toán</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($order->status == 'pending')
                                                    <span class="badge bg-warning">Chờ xác nhận</span>
                                                @elseif($order->status == 'confirmed')
                                                    <span class="badge bg-primary">Đã xác nhận</span>
                                                @elseif($order->status == 'shipping')
                                                    <span class="badge bg-info">Đang giao</span>
                                                @elseif($order->status == 'completed')
                                                    <span class="badge bg-success">Hoàn thành</span>
                                                @else
                                                    <span class="badge bg-danger">Đã hủy</span>
                                                @endif
                                            </td>

                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>

                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="las la-eye"></i>
                                                    </a>

                                                </div>
                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>

                            {{-- Pagination --}}
                            <div class="d-flex justify-content-end">
                                {{ $orders->links() }}
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#toggleFilterBtn').on('click', function() {
                $('#filterForm').slideToggle(200);
            });
        });
    </script>
@endpush
