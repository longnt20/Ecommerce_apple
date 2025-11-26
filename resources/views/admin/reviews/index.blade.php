@extends('admin.layouts.app')
@section('title', 'Danh sách đánh giá')
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

        .user-table th,
        .user-table td {
            vertical-align: middle;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
@endpush
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí đánh giá</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí đánh giá</a></li>
                        <li class="breadcrumb-item">Danh sách đánh giá</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row cursor-pointer">
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card total-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-primary">
                        <i class="la la-images"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Tổng số đánh giá</h5>
                    <h3 class="card-text fw-bold">{{ $summary['total'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card approved-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-success">
                        <i class="la la-check-circle"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Số đánh giá được hiển thị</h5>
                    <h3 class="card-text fw-bold text-success">{{ $summary['public'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card pending-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-warning">
                        <i class="la la-ban"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Số đánh giá bị ẩn</h5>
                    <h3 class="card-text fw-bold text-warning">{{ $summary['hidden'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card rejected-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-danger">
                        <i class="la la-trash"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Số đánh giá xấu (< 3 sao)</h5>
                            <h3 class="card-text fw-bold text-danger">
                                {{ $summary['bad_reviews'] ?? 0 }}
                            </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Danh sách đánh giá</h4>
                    <button class="btn btn-outline-primary btn-sm" id="toggleFilterBtn">
                        <i class="ri-filter-3-line"></i> Bộ lọc
                    </button>
                </div><!-- end card header -->

                {{-- Form lọc --}}
                <div class="card-body" id="filterForm" style="display: none;">
                    <form action="{{ route('admin.reviews.index') }}" method="GET">
                        <div class="row g-3">
                            {{-- Họ tên --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Người đánh giá</label>
                                <input type="text" name="us" value="{{ request('us') }}" class="form-control"
                                    placeholder="Nhập tên người dùng">
                            </div>

                            {{-- Sản phẩm --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sản phẩm</label>
                                <select name="product" class="form-select">
                                    <option value="">-- Tất cả --</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}"
                                            {{ request('product') == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Trạng thái --}}
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">-- Tất cả --</option>
                                    <option value="public" {{ request('status') == 'public' ? 'selected' : '' }}>Public
                                    </option>
                                    <option value="hidden" {{ request('status') == 'hidden' ? 'selected' : '' }}>Hidden
                                    </option>
                                </select>
                            </div>

                            {{-- Số sao --}}
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Số sao</label>
                                <select name="rating" class="form-select">
                                    <option value="">-- Số sao --</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}"
                                            {{ request('rating') == $i ? 'selected' : '' }}>
                                            {{ $i }} sao
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            {{-- Nút lọc và reset --}}
                            <div class="col-md-12 d-flex justify-content-end gap-2 mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line"></i> Lọc
                                </button>
                                <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ri-refresh-line"></i> Đặt lại
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="listjs-table" id="customerList">

                        <div class="table-responsive table-card mt-3 mb-1">
                            <table class="table align-middle table-nowrap" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>STT</th>
                                        <th data-sort="customer_name">Người đánh giá</th>
                                        <th data-sort="cate">Sản phẩm</th>
                                        <th data-sort="phone">Số sao</th>
                                        <th data-sort="date">Trải nghiệm</th>
                                        <th data-sort="phone">Trạng thái</th>
                                        <th data-sort="phone">Ngày gửi</th>
                                        <th data-sort="action">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reviews as $index => $review)
                                        <tr class="border-b">

                                            {{-- STT --}}
                                            <td class="text-center">
                                                {{ $reviews->firstItem() + $index }}
                                            </td>

                                            {{-- Tên người dùng --}}
                                            <td>
                                                @if ($review->user)
                                                    <img src="{{ $review->user->avatar ?? 'https://res.cloudinary.com/dvrexlsgx/image/upload/v1732148083/Avatar-trang-den_apceuv_pgbce6.png' }}"
                                                        width="50px">
                                                @endif
                                                {{ $review->user->name ?? 'Ẩn danh' }}

                                            </td>

                                            {{-- Sản phẩm --}}
                                            <td>
                                                {{ $review->product->name ?? '---' }} <br>
                                                {{ $review->productVariant->attribute_summary ?? '---' }}
                                            </td>

                                            {{-- Số sao --}}
                                            <td>
                                                <div class="flex items-center">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <svg class="{{ $i <= $review->rating ? 'text-warning' : 'text-light' }}"
                                                            viewBox="0 0 24 24"
                                                            style="width:20px;height:20px;fill:currentColor;">
                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22
                                                        9.24l-7.19-.61L12 2 9.19 8.63
                                                        2 9.24l5.46 4.73L5.82 21z" />
                                                        </svg>
                                                    @endfor
                                                </div>
                                            </td>

                                            {{-- Trải nghiệm người dùng --}}
                                            @php
                                                // lấy bản ghi tương ứng cho từng tiêu chí (nếu có)
                                                $expFabric = $review->experiences->firstWhere(
                                                    'criterion',
                                                    'Hiệu năng',
                                                );
                                                $expFit = $review->experiences->firstWhere('criterion', 'Thời lượng pin');
                                                $expColor = $review->experiences->firstWhere('criterion', 'Chất lượng camera');

                                                $fabricRating = $expFabric->rating ?? 0;
                                                $fitRating = $expFit->rating ?? 0;
                                                $colorRating = $expColor->rating ?? 0;
                                            @endphp
                                            <td>
                                                Hiệu năng :
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="{{ $i <= $fabricRating ? 'text-warning' : 'text-light' }}"
                                                        viewBox="0 0 24 24"
                                                        style="width:20px;height:20px;fill:currentColor;">
                                                        <path
                                                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                                    </svg>
                                                @endfor
                                                <br>
                                                Thời lượng pin : @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="{{ $i <= $fitRating ? 'text-warning' : 'text-light' }}"
                                                        viewBox="0 0 24 24"
                                                        style="width:20px;height:20px;fill:currentColor;">
                                                        <path
                                                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                                    </svg>
                                                @endfor <br>
                                                MChất lượng camera : @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="{{ $i <= $colorRating ? 'text-warning' : 'text-light' }}"
                                                        viewBox="0 0 24 24"
                                                        style="width:20px;height:20px;fill:currentColor;">
                                                        <path
                                                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                                    </svg>
                                                @endfor
                                            </td>

                                            {{-- Trạng thái --}}
                                            <td>
                                                @if ($review->status === 'public')
                                                    <span class="badge bg-success w-75">Public</span>
                                                @else
                                                    <span class="badge bg-danger w-75">Hidden</span>
                                                @endif
                                            </td>

                                            {{-- Ngày gửi --}}
                                            <td>
                                                {{ $review->created_at->format('d/m/Y H:i') }}
                                            </td>

                                            {{-- Hành động --}}
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <div class="edit">
                                                        <form
                                                            action="{{ route('admin.reviews.toggleStatus', $review->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PATCH')

                                                            <button class="btn btn-sm btn-warning"
                                                                title="{{ $review->status === 'public' ? 'Ẩn đánh giá' : 'Hiển thị đánh giá' }}">
                                                                @if ($review->status === 'public')
                                                                    {{-- Icon ẩn --}}
                                                                    <i class="ri-eye-off-line"></i>
                                                                @else
                                                                    {{-- Icon hiện --}}
                                                                    <i class="ri-eye-line"></i>
                                                                @endif
                                                            </button>
                                                        </form>

                                                    </div>
                                                    <div class="show">
                                                        <button class="btn btn-sm btn-info show-item-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#reviewDetailModal{{ $review->id }}">
                                                            <i class="las la-eye"></i>
                                                        </button>
                                                    </div>





                                            </td>

                                        </tr>
                                        <!-- Modal Chi tiết đánh giá -->
                                        <div class="modal fade" id="reviewDetailModal{{ $review->id }}"
                                            tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Chi tiết đánh giá của người dùng:
                                                            {{ $review->user->name }}</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">

                                                        <!-- Người dùng -->
                                                        <div class="mb-3">
                                                            <strong>Người đánh giá:</strong>
                                                            {{ $review->user->name ?? 'Ẩn danh' }}
                                                        </div>

                                                        <!-- Sản phẩm -->
                                                        <div class="mb-3">
                                                            <strong>Sản phẩm:</strong>
                                                            {{ $review->product->name }}
                                                            <br>
                                                            <small>
                                                                Phân loại hàng:
                                                                {{ $review->productVariant->attribute_summary ?? '---' }}
                                                            </small>
                                                        </div>

                                                        <!-- Số sao -->
                                                        <div class="mb-3">
                                                            <strong>Số sao:</strong>
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <svg class="{{ $i <= $review->rating ? 'text-warning' : 'text-light' }}"
                                                                    viewBox="0 0 24 24"
                                                                    style="width:20px;height:20px;fill:currentColor;">
                                                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22
                                    9.24l-7.19-.61L12 2 9.19 8.63
                                    2 9.24l5.46 4.73L5.82 21z" />
                                                                </svg>
                                                            @endfor
                                                        </div>

                                                        <!-- Trải nghiệm -->
                                                        <div class="mb-3">
                                                            <strong>Trải nghiệm:</strong> <br>

                                                            <div>
                                                                <strong>• Chất liệu vải:</strong>
                                                                @php
                                                                    $fabric = $review->experiences
                                                                        ->where('criterion', 'Chất liệu vải')
                                                                        ->first();
                                                                @endphp
                                                                @if ($fabric)
                                                                    @for ($i = 1; $i <= 5; $i++)
                                                                        <svg class="{{ $i <= $fabric->rating ? 'text-warning' : 'text-light' }}"
                                                                            viewBox="0 0 24 24"
                                                                            style="width:20px;height:20px;fill:currentColor;">
                                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22
                                            9.24l-7.19-.61L12 2 9.19 8.63
                                            2 9.24l5.46 4.73L5.82 21z" />
                                                                        </svg>
                                                                    @endfor
                                                                @else
                                                                    Không có
                                                                @endif
                                                            </div>

                                                            <div>
                                                                <strong>• Độ vừa vặn:</strong>
                                                                @php
                                                                    $fit = $review->experiences
                                                                        ->where('criterion', 'Độ vừa vặn')
                                                                        ->first();
                                                                @endphp
                                                                @if ($fit)
                                                                    @for ($i = 1; $i <= 5; $i++)
                                                                        <svg class="{{ $i <= $fit->rating ? 'text-warning' : 'text-light' }}"
                                                                            viewBox="0 0 24 24"
                                                                            style="width:20px;height:20px;fill:currentColor;">
                                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22
                                            9.24l-7.19-.61L12 2 9.19 8.63
                                            2 9.24l5.46 4.73L5.82 21z" />
                                                                        </svg>
                                                                    @endfor
                                                                @else
                                                                    Không có
                                                                @endif
                                                            </div>

                                                            <div>
                                                                <strong>• Màu sắc:</strong>
                                                                @php
                                                                    $color = $review->experiences
                                                                        ->where('criterion', 'Màu sắc')
                                                                        ->first();
                                                                @endphp
                                                                @if ($color)
                                                                    @for ($i = 1; $i <= 5; $i++)
                                                                        <svg class="{{ $i <= $color->rating ? 'text-warning' : 'text-light' }}"
                                                                            viewBox="0 0 24 24"
                                                                            style="width:20px;height:20px;fill:currentColor;">
                                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22
                                            9.24l-7.19-.61L12 2 9.19 8.63
                                            2 9.24l5.46 4.73L5.82 21z" />
                                                                        </svg>
                                                                    @endfor
                                                                @else
                                                                    Không có
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Nội dung -->
                                                        <div class="mb-3">
                                                            <strong>Nội dung:</strong> <br>
                                                            <div class="p-2 bg-light rounded">
                                                                {{ $review->content ?? 'Không có nội dung' }}
                                                            </div>
                                                        </div>
                                                        <!-- Ảnh đính kèm -->
                                                        <div class="mb-3">
                                                            <strong>Ảnh đính kèm:</strong> <br>

                                                            @if ($review->media->count())
                                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                                    @foreach ($review->media as $media)
                                                                        <a href="{{ $media->url }}" target="_blank">
                                                                            <img src="{{ Storage::url($media->url) }}"
                                                                                alt="Ảnh đánh giá"
                                                                                style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <div class="p-2 bg-light rounded">
                                                                    Không có ảnh đính kèm
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!-- Trạng thái -->
                                                        <div class="mb-3">
                                                            <strong>Trạng thái:</strong>
                                                            @if ($review->status === 'public')
                                                                <span class="badge bg-success">Public</span>
                                                            @else
                                                                <span class="badge bg-danger">Hidden</span>
                                                            @endif
                                                        </div>

                                                        <!-- Ngày gửi -->
                                                        <div class="mb-3">
                                                            <strong>Ngày gửi:</strong>
                                                            {{ $review->created_at->format('d/m/Y H:i') }}
                                                        </div>

                                                    </div>

                                                    <div class="modal-footer">
                                                         <form
                                                            action="{{ route('admin.reviews.toggleStatus', $review->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PATCH')

                                                            <button class="btn btn-warning"
                                                                >
                                                                @if ($review->status === 'public')
                                                                    Ẩn đánh giá
                                                                @else
                                                                    Hiển thị đánh giá
                                                                @endif
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Đóng</button>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>

                            </table>

                            <div class="noresult" style="display: none">
                                <div class="text-center">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px"></lord-icon>
                                    <h5 class="mt-2">Sorry! No Result Found</h5>
                                    <p class="text-muted mb-0">We've searched more than 150+ Orders We did not find any
                                        orders for you search.</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <div class="pagination-wrap hstack gap-2">

                                {{-- Nút Previous --}}
                                @if ($reviews->onFirstPage())
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0);">Previous</a>
                                @else
                                    <a class="page-item pagination-prev"
                                        href="{{ $reviews->previousPageUrl() }}">Previous</a>
                                @endif

                                {{-- Các số trang --}}
                                <ul class="pagination listjs-pagination mb-0">
                                    @foreach ($reviews->getUrlRange(1, $reviews->lastPage()) as $page => $url)
                                        <li class="page-item {{ $page == $reviews->currentPage() ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Nút Next --}}
                                @if ($reviews->hasMorePages())
                                    <a class="page-item pagination-next" href="{{ $reviews->nextPageUrl() }}">Next</a>
                                @else
                                    <a class="page-item pagination-next disabled" href="javascript:void(0);">Next</a>
                                @endif

                            </div>
                        </div>

                    </div>
                </div><!-- end card -->
            </div>
            <!-- end col -->
        </div>
        <!-- end col -->
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