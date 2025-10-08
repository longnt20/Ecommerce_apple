@extends('admin.layouts.app')
@section('title', 'Danh sách người dùng đã xóa')
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
                <h4 class="mb-sm-0">Quản lí người dùng đã xóa</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí người dùng đã xóa</a></li>
                        <li class="breadcrumb-item">Danh sách người dùng đã xóa</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Danh sách người dùng đã xóa</h4>

                </div><!-- end card header -->

                <div class="card-body">
                    <div class="listjs-table" id="customerList">
                        <div class="row g-4 mb-3">
                            <div class="col-sm-auto">
                                <div>
                                    <button class="btn btn-success" id="restoreSelected">
                                        <i class=" ri-restart-line"> Khôi phục</i>
                                    </button>
                                    <button class="btn btn-danger" id="deleteSelected">
                                        <i class="ri-delete-bin-2-line"> Xóa nhiều</i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-sm">
                                <div class="d-flex justify-content-sm-end">
                                    <div class="search-box ms-2">
                                        <input type="text" class="form-control search" placeholder="Search...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive table-card mt-3 mb-1">
                            <table class="table align-middle table-nowrap" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="checkAll"
                                                    value="option">
                                            </div>
                                        </th>
                                        <th data-sort="customer_id">ID</th>
                                        <th data-sort="customer_name">Tên người dùng</th>
                                        <th data-sort="email">Ảnh</th>
                                        <th data-sort="cate">Email</th>
                                        <th data-sort="phone">Số điện thoại</th>
                                        <th data-sort="phone">Xác minh email</th>
                                        <th data-sort="phone">Vai trò</th>
                                        <th data-sort="date">Trạng thái</th>
                                        <th data-sort="phone">Ngày tham gia</th>
                                        <th data-sort="action">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach ($usersDeleted as $item)
                                        <tr>
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child"
                                                        value="{{ $item->id }}">
                                                </div>
                                            </th>
                                            <td class="customer_id">{{ $item->id }}</td>
                                            <td class="customer_name">{{ $item->name }}</td>
                                            <td class="email">
                                                <img src="{{ $item->avatar ? Storage::url($item->avatar) : \App\Http\Controllers\Admin\UserController::URLIMAGEDEFAULT }}"
                                                    width="50">
                                            </td>
                                            <td class="customer_name">{{ $item->email }}</td>
                                            <td class="phone">{{ $item->phone ?? 'Chưa có thông tin' }}</td>
                                            <td>
                                                <div class="form-check form-switch form-switch-warning">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        name="email_verified" value="{{ $item->id }}"
                                                        @checked($item->email_verified_at != null)>
                                                </div>
                                            </td>
                                            <td class="phone">{{ $item->role }}</td>
                                            <td class="status">
                                                @if ($item->status == 'active')
                                                    <span
                                                        class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                                @elseif($item->status == 'inactive')
                                                    <span
                                                        class="badge bg-warning-subtle text-warning text-uppercase">Inactive</span>
                                                @else
                                                    <span
                                                        class="badge bg-warning-subtle text-warning text-uppercase">Block</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ optional($item->created_at)->format('d/m/Y') ?? 'NULL' }}
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <div class="edit">
                                                        <form action="{{ route('admin.users.restore', $item->id) }}"
                                                            method="post">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="btn btn-sm btn-success edit-item-btn btn-remove"
                                                                data-bs-toggle="modal" data-bs-target="#showModal" data-name="{{ $item->name }}">
                                                                <i class="las la-redo-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="remove">
                                                        <form method="POST"
                                                            action="{{ route('admin.users.force-delete', $item->id) }}"
                                                            class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger remove-item-btn btn-forcedelete"
                                                                data-name="{{ $item->name }}">
                                                                <i class="ri-delete-bin-2-line"></i>
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
                                @if ($usersDeleted->onFirstPage())
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0);">Previous</a>
                                @else
                                    <a class="page-item pagination-prev"
                                        href="{{ $usersDeleted->previousPageUrl() }}">Previous</a>
                                @endif

                                {{-- Các số trang --}}
                                <ul class="pagination listjs-pagination mb-0">
                                    @foreach ($usersDeleted->getUrlRange(1, $usersDeleted->lastPage()) as $page => $url)
                                        <li class="page-item {{ $page == $usersDeleted->currentPage() ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Nút Next --}}
                                @if ($usersDeleted->hasMorePages())
                                    <a class="page-item pagination-next"
                                        href="{{ $usersDeleted->nextPageUrl() }}">Next</a>
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
