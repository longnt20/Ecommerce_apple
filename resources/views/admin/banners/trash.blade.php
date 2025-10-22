@extends('admin.layouts.app')
@section('title', 'Danh sách banner đã xóa')
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
                <h4 class="mb-sm-0">Quản lí banner đã xóa</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí banner đã xóa</a></li>
                        <li class="breadcrumb-item">Danh sách banner đã xóa</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Danh sách banner đã xóa</h4>

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
                                          <th>STT</th>
                                            <th>Tiêu đề</th>
                                            <th>Ảnh</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Ngày cập nhật</th>
                                            <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                   @foreach ($bannersDeleted as $banner)
                                            <tr data-id="{{ $banner->id }}">
                                                <th scope="row">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="itemID"
                                                            value="{{ $banner->id }}" data-id="{{ $banner->id }}">
                                                    </div>
                                                </th>
                                                <td class="order">{{ $loop->iteration }}</td>
                                                <td class="customer_name">{{ $banner->title }}</td>
                                                <td>
                                                    @if ($banner->image)
                                                        <img src="{{ Storage::url($banner->image) }}" alt=""
                                                            class="img-thumbnail" style="max-width: 100px">
                                                    @else
                                                        <span class="text-muted">Không có ảnh</span>
                                                    @endif
                                                </td>
                                                @if ($banner->status)
                                                    <td class="status"><span class="badge bg-success-subtle text-success">
                                                            Hoạt động
                                                        </span></td>
                                                @else
                                                    <td class="status"><span class="badge bg-danger-subtle text-danger">
                                                            Không hoạt động
                                                        </span></td>
                                                @endif

                                                <td class="date">{{ $banner->created_at }}</td>
                                                <td class="date">{{ $banner->updated_at }}</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                    <div class="edit">
                                                        <form action="{{ route('admin.banners.restore', $banner->id) }}"
                                                            method="post">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="btn btn-sm btn-success edit-item-btn btn-remove"
                                                                data-bs-toggle="modal" data-bs-target="#showModal" data-name="{{ $banner->title }}">
                                                                <i class="las la-redo-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="remove">
                                                        <form method="POST"
                                                            action="{{ route('admin.banners.force-delete', $banner->id) }}"
                                                            class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger remove-item-btn btn-forcedelete"
                                                                data-name="{{ $banner->title }}">
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
                                @if ($bannersDeleted->onFirstPage())
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0);">Previous</a>
                                @else
                                    <a class="page-item pagination-prev"
                                        href="{{ $bannersDeleted->previousPageUrl() }}">Previous</a>
                                @endif

                                {{-- Các số trang --}}
                                <ul class="pagination listjs-pagination mb-0">
                                    @foreach ($bannersDeleted->getUrlRange(1, $bannersDeleted->lastPage()) as $page => $url)
                                        <li class="page-item {{ $page == $bannersDeleted->currentPage() ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Nút Next --}}
                                @if ($bannersDeleted->hasMorePages())
                                    <a class="page-item pagination-next"
                                        href="{{ $bannersDeleted->nextPageUrl() }}">Next</a>
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
