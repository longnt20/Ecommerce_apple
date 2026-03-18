@extends('admin.layouts.app')
@push('page-css')
    <!-- plugin css -->
    <link href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .stat-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            height: 150px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .card-title-custom {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #6c757d;
        }

        .action-buttons .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .coupon-table th {
            background-color: #f5f7fa;
        }
    </style>
@endpush
@php
    $title = 'Danh sách mã giảm giá';
@endphp
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Quản lí mã giảm giá</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Danh sách coupon</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <div class="row cursor-pointer">
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <div class="card stat-card border-0">
                    <div class="card-body text-center">
                        <div class="stat-icon text-primary">
                            <i class="ri-coupon-3-line"></i>
                        </div>
                        <h6 class="card-title-custom">TỔNG SỐ MÃ GIẢM GIÁ</h6>
                        <p class="stat-value text-primary">{{ $couponCounts->total_coupons ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <div class="card stat-card border-0">
                    <div class="card-body text-center">
                        <div class="stat-icon text-success">
                            <i class="ri-check-double-line"></i>
                        </div>
                        <h6 class="card-title-custom">ĐANG HOẠT ĐỘNG</h6>
                        <p class="stat-value text-success">{{ $couponCounts->active_coupons ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <div class="card stat-card border-0">
                    <div class="card-body text-center">
                        <div class="stat-icon text-warning">
                            <i class="ri-timer-line"></i>
                        </div>
                        <h6 class="card-title-custom">SẮP HẾT HẠN</h6>
                        <p class="stat-value text-warning">{{ $couponCounts->expire_coupons ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <div class="card stat-card border-0">
                    <div class="card-body text-center">
                        <div class="stat-icon text-danger">
                            <i class="ri-archive-line"></i>
                        </div>
                        <h6 class="card-title-custom">ĐÃ SỬ DỤNG</h6>
                        <p class="stat-value text-danger">{{ $couponCounts->used_coupons ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Danh sách mã giảm giá</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#importModal">Import dữ liệu</button>
                            <a href="{{ route('admin.coupons.exportCoupon') }}" class="btn btn-sm btn-success h-75">Export dữ
                                liệu</a>
                            <button class="btn btn-sm btn-primary" id="toggleAdvancedSearch">
                                Tìm kiếm nâng cao
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary" type="button" id="filterDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-2-line"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown"
                                    style="min-width: 500px;">
                                    <div class="container">
                                        <div class="container">
                                            <div class="row">
                                                <li>
                                                    <label for="amountRange" class="form-label">Số lượt sử dụng</label>

                                                    <div class="d-flex justify-content-between">
                                                        <span id="amountMin">0</span>
                                                        <span id="amountMax">1000</span>
                                                    </div>

                                                    <div class="d-flex justify-content-between">
                                                        <input type="range" class="form-range w-100" id="amountMinRange"
                                                            name="used_count" min="0" max="1000" step="10"
                                                            value="0" oninput="updateRange()" data-filter>

                                                    </div>
                                                </li>
                                                <li class="col-6">
                                                    <div class="mb-2">
                                                        <label for="startDate" class="form-label">Ngày bắt đầu</label>
                                                        <input type="date" class="form-control form-control-sm"
                                                            name="start_date" id="startDate" data-filter
                                                            value="{{ request()->input('start_date') ?? '' }}">
                                                    </div>
                                                </li>
                                                <li class="col-6">
                                                    <div class="mb-2">
                                                        <label for="endDate" class="form-label">Ngày kết thúc</label>
                                                        <input type="date" class="form-control form-control-sm"
                                                            name="expire_date" id="endDate" data-filter
                                                            value="{{ request()->input('expire_date') ?? '' }}">
                                                    </div>
                                                </li>
                                            </div>
                                            <li class="mt-2">
                                                <button class="btn btn-sm btn-primary w-100" id="applyFilter">Áp
                                                    dụng</button>
                                            </li>

                                        </div>
                                    </div>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Tìm kiếm nâng cao -->
                    <div id="advancedSearch" class="card-header" style="display:none;">
                        <form>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Mã giảm giá</label>
                                    <input class="form-control form-control-sm" name="code" type="text"
                                        value="{{ request()->input('code') ?? '' }}" placeholder="Nhập mã giảm giá..."
                                        data-advanced-filter>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tên mã giảm giá</label>
                                    <input class="form-control form-control-sm" name="name" type="text"
                                        value="{{ request()->input('name') ?? '' }}" placeholder="Nhập tên mã giảm giá..."
                                        data-advanced-filter>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Người tạo</label>
                                    <input class="form-control form-control-sm" name="user_id" type="text"
                                        value="{{ request()->input('user_id') ?? '' }}" placeholder="Nhập người tạo..."
                                        data-advanced-filter>
                                </div>
                                <div class="col-md-3">
                                    <label for="statusItem" class="form-label">Loại giảm giá</label>
                                    <select class="form-select form-select-sm" name="discount_type" id="statusItem"
                                        data-advanced-filter>
                                        <option value="">Chọn loại giảm giá</option>
                                        <option @selected(request()->input('discount_type') === 'percentage') value="percentage">Phần trăm</option>
                                        <option @selected(request()->input('discount_type') === 'fixed') value="fixed">Giảm trực tiếp</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <label for="statusItem" class="form-label">Trạng thái</label>
                                    <select class="form-select form-select-sm" name="status" id="statusItem"
                                        data-advanced-filter>
                                        <option value="">Chọn trạng thái</option>
                                        <option @selected(request()->input('status') === '1') value="1">Hoạt động</option>
                                        <option @selected(request()->input('status') === '0') value="0">Không hoạt động</option>
                                    </select>
                                </div>
                                <div class="mt-3 text-end">
                                    <button class="btn btn-sm btn-success" type="reset" id="resetFilter">Reset</button>
                                    <button class="btn btn-sm btn-primary" id="applyAdvancedFilter">Áp dụng</button>
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="card-body" id="item_List">
                        <div class="listjs-table">
                            <div class="row g-4 mb-3">
                                <div class="col-sm-auto">
                                    <div>
                                        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary add-btn"><i
                                                class="ri-add-line align-bottom me-1"></i> Thêm mới</a>
                                        <button class="btn btn-danger" id="deleteSelected">
                                            <i class="ri-delete-bin-2-line"> Xóa nhiều</i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-sm">
                                    <div class="d-flex justify-content-sm-end">
                                        <div class="search-box ms-2">
                                            <form action="{{ route('admin.coupons.index') }}" method="get">
                                                <input type="text" name="query" class="form-control search"
                                                    placeholder="Search..." value="{{ old('query') }}">
                                                <i class="ri-search-line search-icon"></i>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive table-card mt-3 mb-1">
                                <table class="table align-middle table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 50px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll"
                                                        value="option">
                                                </div>
                                            </th>
                                            <th>ID</th>
                                            <th>Người tạo</th>
                                            <th>Chương trình</th>
                                            <th>Mã giảm giá</th>
                                            <th>Giảm giá</th>
                                            <th>Trạng Thái</th>
                                            <th>Ngày bắt đầu</th>
                                            <th>Ngày kết thúc</th>
                                            <th>Số lượng</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list form-check-all">
                                        @foreach ($coupons as $coupon)
                                            <tr>
                                                <th scope="row">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="itemID"
                                                            value="{{ $coupon->id }}">
                                                    </div>
                                                </th>

                                                <td class="id">{{ $loop->iteration }}</td>
                                                <td class="id">{{ $coupon->user->name }}</td>
                                                <td class="customer_name">{{ $coupon->name }}</td>
                                                <td class="date">{{ $coupon->code }}</td>
                                                <td class="date">{{ number_format($coupon->discount_value) }}
                                                    {{ $coupon->discount_type == 'fixed' ? 'VND' : '%' }}
                                                </td>
                                                @if ($coupon->status)
                                                    <td class="status"><span class="badge bg-success text-uppercase">
                                                            Hoạt động
                                                        </span></td>
                                                @else
                                                    <td class="status"><span class="badge bg-danger text-uppercase">
                                                          Hết hạn
                                                        </span></td>
                                                @endif

                                                <td class="date">{{ $coupon->start_date ? \Carbon\Carbon::parse($coupon->start_date)->format('d/m/Y') : '' }}</td>
                                                <td class="date">{{ $coupon->expire_date ? \Carbon\Carbon::parse($coupon->expire_date)->format('d/m/Y') : '' }}</td>
                                                <td class="date">{{ $coupon->max_usage ?? 0 }}</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <div class="remove">
                                                            <a href="{{ route('admin.coupons.edit', $coupon->id) }}">
                                                                <button class="btn btn-sm btn-warning edit-item-btn">
                                                                    <span class="ri-edit-box-line"></span>
                                                                </button>
                                                            </a>
                                                        </div>
                                                        <div class="edit">
                                                            <a href="{{ route('admin.coupons.show', $coupon->id) }}">
                                                                <button class="btn btn-sm btn-info edit-item-btn">
                                                                    <span class="ri-eye-line"></span>
                                                                </button>
                                                            </a>
                                                        </div>
                                                        <div class="remove">
                                                            <a href="{{ route('admin.coupons.destroy', $coupon->id) }}"
                                                                class="sweet-confirm btn btn-sm btn-danger remove-item-btn">
                                                                <span class="ri-delete-bin-7-line"></span>
                                                            </a>
                                                        </div>

                                                    </div>
                                                </td>


                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{ $coupons->appends(request()->query())->links() }}
                        </div>
                    </div><!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end col -->
        </div>
        <!-- end row -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Import Dữ Liệu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="importForm" action="{{ route('admin.coupons.import') }}" method="POST"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="importFile" class="form-label">Chọn file để import:</label>
                                <input type="file" class="form-control" name="file" id="file"
                                       accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">
                                    Định dạng hỗ trợ: Excel (.xlsx, .xls) hoặc CSV (.csv)
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="ri-upload-2-line me-1"></i> Tiến hành Import
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')

    <script>
        var routeUrlFilter = "{{ route('admin.coupons.index') }}";
        var routeDeleteAll = "{{ route('admin.coupons.destroy', ':itemID') }}";

        function updateRange() {
            let rangeValue = document.getElementById("amountMinRange").value;
            document.getElementById("amountMin").textContent = rangeValue;
        }

        $(document).on('click', '#resetFilter', function() {
            window.location = routeUrlFilter;
        });
    </script>

    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
    <script src="{{ asset('assets/js/common/checkall-option.js') }}"></script>
    <script src="{{ asset('assets/js/common/delete-all-selected.js') }}"></script>
    <script src="{{ asset('assets/js/common/restore-all-selected.js') }}"></script>
    <script src="{{ asset('assets/js/common/filter.js') }}"></script>
    <script src="{{ asset('assets/js/common/search.js') }}"></script>
    <script src="{{ asset('assets/js/common/resetFilter.js') }}"></script>
    <script src="{{ asset('assets/js/common/handle-ajax-search&filter.js') }}"></script>
@endpush
