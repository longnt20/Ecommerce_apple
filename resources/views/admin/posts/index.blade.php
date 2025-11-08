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
@section('title')
    Danh sách bài viết
@endsection
@section('content')
    <div class="row cursor-pointer">
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card total-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-primary">
                        <i class="la la-images"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Tổng số bài viết</h5>
                    <h3 class="card-text fw-bold">{{ $posts_total ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card approved-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-success">
                        <i class="la la-check-circle"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Số bài viết đã xuất bản</h5>
                    <h3 class="card-text fw-bold text-success">{{ $posts_published ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card pending-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-warning">
                        <i class="la la-ban"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Số bài viết nổi bật</h5>
                    <h3 class="card-text fw-bold text-warning">{{ $posts_is_hot ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 mb-3">
            <div class="card stats-card rejected-card">
                <div class="card-body text-center">
                    <div class="stat-icon text-danger">
                        <i class="la la-trash"></i>
                    </div>
                    <h5 class="card-title text-muted mb-2">Số bài viết đã xóa</h5>
                    <h3 class="card-text fw-bold text-danger">{{ $posts_deleted ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Quản lí bài viết</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Danh sách bài viết</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title mb-0">Danh sách bài viết</h4>
                        <button class="btn btn-outline-primary btn-sm" id="toggleFilterBtn">
                            <i class="ri-filter-3-line"></i> Bộ lọc
                        </button>
                    </div><!-- end card header -->

                    {{-- Form lọc --}}
                    <div class="card-body" id="filterForm" style="display: none;">
                        <form action="{{ route('admin.posts.index') }}" method="GET">
                            <div class="row g-3">
                                {{-- Họ tên --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Tiêu đề</label>
                                    <input type="text" name="title" value="{{ request('title') }}" class="form-control"
                                        placeholder="Nhập tiêu đề banner">
                                </div>

                                {{-- Trạng thái --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Trạng thái</label>
                                    <select name="status" class="form-select">
                                        <option value="" selected>-- Tất cả --</option>
                                        <option value="1">Hoạt
                                            động
                                        </option>
                                        <option value="0">
                                            Ngừng
                                            hoạt động</option>
                                    </select>
                                </div>

                                {{-- Nút lọc và reset --}}
                                <div class="col-md-12 d-flex justify-content-end gap-2 mt-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-search-line"></i> Lọc
                                    </button>
                                    <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i> Đặt lại
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body" id="item_List">
                        <div class="listjs-table" id="customerList">
                            <div class="row g-4 mb-3">
                                <div class="col-sm-auto">
                                    <div>
                                        <a href="{{ route('admin.posts.create') }}" class="btn btn-success add-btn"><i
                                                class="ri-add-line align-bottom me-1"></i> Thêm mới</a>
                                        <button class="btn btn-soft-danger" onClick="deleteMultiple()"><i
                                                class="ri-delete-bin-2-line"></i></button>
                                    </div>
                                </div>
                                <div class="col-sm">
                                    <div class="d-flex justify-content-sm-end">
                                        <div class="search-box ms-2">
                                            <input type="text" name="search_full" id="searchFull"
                                                class="form-control search" placeholder="Tìm kiếm..." data-search
                                                value="{{ request()->input('search_full') ?? '' }}">
                                            <button id="search-full" class="ri-search-line search-icon m-0 p-0 border-0"
                                                style="background: none;"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive table-card mt-3 mb-1">
                                <table class="table align-middle table-nowrap" id="customerTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 50px;">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th>STT</th>
                                            <th>Tiêu đề</th>
                                            <th>Ảnh bìa</th>
                                            <th>Tác giả</th>
                                            <th>Danh mục</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày đăng tải</th>
                                            <th>Hành Động</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @foreach ($posts as $post)
                                            <tr>
                                                <th scope="row">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="itemID"
                                                            value="{{ $post->id }}">
                                                    </div>
                                                </th>

                                                <td class="customer_name">{{ $loop->iteration }}</td>
                                                <td>
                                                    <h6 class="mb-0 text-truncate" style="max-width: 250px;">
                                                        {{ $post->title }}</h6>
                                                </td>
                                                <td>
                                                    <img class="rounded shadow-sm"
                                                        src="{{ Storage::url($post->thumbnail) }}" alt="Hình đại diện"
                                                        width="80" height="50" style="object-fit: cover;">
                                                </td>
                                                <td class="text-danger fw-bold">{{ $post->user->name ?? '' }}</td>
                                                <td>{{ $post->category->name ?? '' }}</td>
                                                <td class="status col-1">
                                                    @if ($post->status === 'published')
                                                        <span class="badge bg-success w-75">
                                                            Xuất bản
                                                        </span>
                                                    @elseif($post->status === 'pending')
                                                        <span class="badge bg-warning w-75">
                                                            Chờ xử lí
                                                        </span>
                                                    @elseif($post->status === 'draft')
                                                        <span class="badge bg-secondary w-75">
                                                            Bản nháp
                                                        </span>
                                                    @elseif($post->status === 'scheduled')
                                                        <span class="badge bg-info w-75">
                                                           Chờ xuất bản
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger w-75">
                                                            Riêng tư
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    {!! $post->published_at
                                                        ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y')
                                                        : '<span class="btn btn-sm btn-soft-warning">Chưa đăng</span>' !!}
                                                </td>


                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <div class="edit">
                                                            <form action="{{ route('admin.posts.edit', $post->id) }}"
                                                                method="get">
                                                                @csrf
                                                                <button class="btn btn-sm btn-warning edit-item-btn">
                                                                    <span class="ri-edit-box-line"></span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        <div class="show">
                                                            <form action="{{ route('admin.posts.show', $post->id) }}"
                                                                method="get">
                                                                @csrf
                                                                <button class="btn btn-sm btn-info show-item-btn"
                                                                    data-bs-toggle="modal" data-bs-target="#showModal">
                                                                    <i class="las la-eye"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        <div class="remove">
                                                            <form method="POST"
                                                                action="{{ route('admin.posts.destroy', $post->id) }}"
                                                                class="d-inline delete-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button"
                                                                    class="btn btn-sm btn-danger remove-item-btn btn-delete"
                                                                    data-name="{{ $post->name }}">
                                                                    <span class="ri-delete-bin-7-line"></span>
                                                                </button>
                                                            </form>

                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
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
                                    @if ($posts->onFirstPage())
                                        <a class="page-item pagination-prev disabled"
                                            href="javascript:void(0);">Previous</a>
                                    @else
                                        <a class="page-item pagination-prev"
                                            href="{{ $posts->previousPageUrl() }}">Previous</a>
                                    @endif

                                    {{-- Các số trang --}}
                                    <ul class="pagination listjs-pagination mb-0">
                                        @foreach ($posts->getUrlRange(1, $posts->lastPage()) as $page => $url)
                                            <li class="page-item {{ $page == $posts->currentPage() ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                            </li>
                                        @endforeach
                                    </ul>

                                    {{-- Nút Next --}}
                                    @if ($posts->hasMorePages())
                                        <a class="page-item pagination-next" href="{{ $posts->nextPageUrl() }}">Next</a>
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
        <!-- end row -->

    </div>
@endsection
@push('scripts')
    <script>
        // Khởi tạo SortableJS cho tbody
        var el = document.getElementById('sortableBanners');
        var sortable = new Sortable(el, {
            handle: 'td', // Cho phép kéo thả từ toàn bộ dòng (có thể thay đổi nếu chỉ muốn kéo ở một cột nhất định)
            animation: 150, // Thêm hiệu ứng khi kéo thả
            onEnd: function(evt) {
                var rows = el.querySelectorAll('tr');
                var orderData = [];

                // Cập nhật thứ tự trong DOM ngay lập tức sau khi kéo thả
                rows.forEach((row, index) => {
                    // Cập nhật lại cột thứ tự trong bảng
                    row.querySelector('.order').textContent = index + 1; // Thứ tự mới
                    var id = row.getAttribute('data-id');
                    orderData.push({
                        id: id,
                        order: index // Cập nhật thứ tự mới cho banner
                    });
                });
                // Gửi dữ liệu order lên server qua AJAX
                fetch("{{ route('admin.banners.updateOrder') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            orderData: orderData
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Hiển thị thông báo Toastr khi cập nhật thành công
                            toastr.success(data.message);
                        } else {
                            // Hiển thị thông báo Toastr khi có lỗi
                            toastr.error("Đã có lỗi xảy ra khi cập nhật thứ tự.");
                        }
                    })
                    .catch((error) => {
                        console.error('Lỗi:', error);
                        toastr.error("Có lỗi xảy ra khi gửi yêu cầu.");
                    });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#toggleFilterBtn').on('click', function() {
                $('#filterForm').slideToggle(200);
            });
        });
    </script>
@endpush
