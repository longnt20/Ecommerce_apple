@extends('admin.layouts.app')

@push('page-css')
    <style>
        .coupon-card {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .coupon-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .coupon-card .card-header {
            padding: 20px;
            border-radius: 12px 12px 0 0;
        }

        .coupon-card .card-header h4 {
            color: white;
            font-weight: 600;
        }

        .coupon-card .card-header .text-danger {
            color: #fcd34d !important;
            font-weight: 700;
        }

        .coupon-card .card-body {
            padding: 30px;
            background-color: white;
        }

        .info-row {
            border-bottom: 1px solid #eef2f7;
            padding: 14px 0;
            margin-bottom: 0;
            transition: background-color 0.2s ease;
        }

        .info-row:hover {
            background-color: #f9fafb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .info-value {
            color: #1f2937;
            font-size: 0.95rem;
        }

        .btn-custom {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-back {
            background-color: #9ca3af;
            border-color: #9ca3af;
            color: white;
        }

        .btn-back:hover {
            background-color: #6b7280;
            border-color: #6b7280;
        }

        .badge-status {
            padding: 6px 10px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .badge-active {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }

        .badge-inactive {
            background: linear-gradient(135deg, #EF4444 0%, #B91C1C 100%);
            color: white;
        }

        .breadcrumb-item a {
            color: #6366f1;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: #4f46e5;
            text-decoration: underline;
        }

        .coupon-code {
            background-color: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .discount-value {
            color: #10b981;
            font-weight: 700;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Chi tiết coupon</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{route('admin.coupons.index')}}">Danh sách coupon</a>
                            </li>
                            <li class="breadcrumb-item active"><a href="{{route('admin.coupons.show', $coupon->id)}}">Chi
                                    tiết coupon</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="coupon-card card">
            <div class="card-header align-items-center d-flex bg-primary">
                <h4 class="card-title mb-0 flex-grow-1">Chi tiết mã giảm giá:  <span
                        class="text-danger">{{ $coupon->name }}</span></h4>
                <div class="d-flex align-items-center">
                    @if ($coupon->status)
                        <span class="badge-status badge-active">
                            <i class="ri-checkbox-circle-line"></i>Đang hoạt động
                        </span>
                    @else
                        <span class="badge-status badge-inactive">
                            <i class="ri-close-circle-line"></i>Không hoạt động
                        </span>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row info-row">
                            <div class="col-md-4 info-label">ID:</div>
                            <div class="col-md-8 info-value">#{{ $coupon->id }}</div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Mã giảm giá:</div>
                            <div class="col-md-8 info-value"><span class="coupon-code">{{ $coupon->code }}</span></div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Tên mã giảm giá:</div>
                            <div class="col-md-8 info-value">{{ $coupon->name }}</div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Loại mã giảm giá:</div>
                            <div class="col-md-8 info-value">
                                @if($coupon->discount_type == 'fixed')
                                    <span class="badge bg-info">Giảm giá cố định</span>
                                @else
                                    <span class="badge bg-primary">Giảm giá phần trăm</span>
                                @endif
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Giá trị giảm giá:</div>
                            <div class="col-md-8 info-value">
                                    <span class="discount-value">{{ number_format($coupon->discount_value) }}
                                        {{ $coupon->discount_type == 'fixed' ? 'VND' : '%' }}</span>
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Giảm giá tối đa:</div>
                            <div class="col-md-8 info-value">{{ number_format($coupon->discount_max_value) }} VND</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Ngày bắt đầu:</div>
                            <div class="col-md-8 info-value">
                                <i class="ri-calendar-line me-1"></i>{{ $coupon->start_date }}
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Ngày kết thúc:</div>
                            <div class="col-md-8 info-value">
                                <i class="ri-calendar-line me-1"></i>{{ $coupon->expire_date }}
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Số lượt sử dụng:</div>
                            <div class="col-md-8 info-value">
                                <i class="ri-user-line me-1"></i>{{$coupon->used_count}}
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Người tạo:</div>
                            <div class="col-md-8 info-value">
                                <i class="ri-user-line me-1"></i>{{$coupon->user->name}}
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Mô tả:</div>
                            <div class="col-md-8 info-value">{{$coupon->description}}</div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Ngày tạo:</div>
                            <div class="col-md-8 info-value">
                                <i class="ri-time-line me-1"></i>{{ $coupon->created_at }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.coupons.index') }}" class="btn btn-custom btn-back">
                                <i class="ri-arrow-left-line me-1"></i> Quay lại
                            </a>
                            <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="btn btn-primary">
                                <i class="ri-edit-line me-1"></i> Chỉnh sửa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
