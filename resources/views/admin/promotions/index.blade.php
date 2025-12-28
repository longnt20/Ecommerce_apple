@extends('admin.layouts.app')
@section('title', 'Danh sách chương trình khuyến mãi')
@push('page-css')
    <style>
        .frame-item {
            width: 300px;
            background: #fff;
            border-radius: 12px;
            padding: 12px;
        }

        .frame-preview-mini {
            position: relative;
            height: 180px;
            border-radius: 10px;
            overflow: hidden;
        }

        .frame-preview-mini div {
            position: absolute;
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center;
        }

        .bg-top {
            top: 30px;
            left: 12px;
            width: 95%;
            height: 55px;
            z-index: 1;
        }

        .bg-bottom {
            top: 50px;
            width: 100%;
            height: 130px;
            z-index: 2;
        }

        .ribbon {
            width: 100%;
            height: 15%;
            top: 35px;
            left: 30%;
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            z-index: 5;
        }
        .title {
            height: 15%;
            top: 38px;
            left: 30%;
            transform: translateX(-50%);
            z-index: 6;
        }
        .decor-left {
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            left: 20px;
            top: 60px;
            width: 40px;
            z-index: 4;
            height: 30%;
        }

        .decor-right {
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            right: -20px;
            top: 40px;
            width: 40px;
            z-index: 4;
            height: 30%;
        }
    </style>
@endpush
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí chương trình khuyến mãi</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí chương trình khuyến mãi</a>
                        </li>
                        <li class="breadcrumb-item">Danh sách chương trình khuyến mãi</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Danh sách chương trình khuyến mãi</h4>

                </div><!-- end card header -->

                <div class="card-body">
                    <div class="listjs-table" id="customerList">
                        <div class="row g-4 mb-3">
                            <div class="col-sm-auto">
                                <div>
                                    <a href="{{ route('admin.promotions.create') }}" class="btn btn-success add-btn"><i
                                            class="ri-add-line align-bottom me-1"></i> Thêm mới</a>
                                    <button class="btn btn-soft-danger" onClick="deleteMultiple()"><i
                                            class="ri-delete-bin-2-line"></i></button>
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
                                        <th data-sort="customer_name">Tên chương trình</th>
                                        <th data-sort="email">Khung</th>
                                        <th data-sort="cate">Danh mục</th>
                                        <th data-sort="phone">Ngày bắt đầu</th>
                                        <th data-sort="phone">Ngày kết thúc</th>
                                        <th>Nổi bật</th>
                                        <th data-sort="date">Trạng thái</th>
                                        <th data-sort="action">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach ($promotions as $item)
                                        <tr>
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child"
                                                        value="{{ $item->id }}">
                                                </div>
                                            </th>
                                            <td class="customer_id">{{ $item->id }}</td>
                                            <td class="customer_name">{{ $item->name }}</td>
                                            <td>
                                                <div class="frame-item">
                                                    <div class="frame-preview-mini mb-2">
                                                        @if ($item->frame->top_background)
                                                            <div class="bg-top"
                                                                style="background-image:url({{ asset(Storage::url($item->frame->top_background)) }})">
                                                            </div>
                                                        @endif

                                                        @if ($item->frame->bottom_background)
                                                            <div class="bg-bottom"
                                                                style="background-image:url({{ asset(Storage::url($item->frame->bottom_background)) }})">
                                                            </div>
                                                        @endif

                                                        @if ($item->frame->ribbon_image)
                                                            <div class="ribbon"
                                                                style="background-image:url({{ asset(Storage::url($item->frame->ribbon_image)) }})">
                                                            </div>
                                                        @endif
                                                        <div class="title">
                                                            <img src="{{ asset('storage/' . $item->thumbnail) }}"
                                                                alt="" width="100px">
                                                        </div>
                                                        @if ($item->frame->left_decor_image)
                                                            <div class="decor-left"
                                                                style="background-image:url({{ asset(Storage::url($item->frame->left_decor_image)) }})">
                                                            </div>
                                                        @endif

                                                        @if ($item->frame->right_decor_image)
                                                            <div class="decor-right"
                                                                style="background-image:url({{ asset(Storage::url($item->frame->right_decor_image)) }})">
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="customer_name">{{ $item->category->name ?? 'Không xác định' }}</td>
                                            <td class="phone">{{ $item->start_date }}</td>
                                            <td class="phone">{{ $item->end_date }}</td>
                                            <td>
                                                <div class="form-check form-switch form-switch-warning">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        name="is_featured" value="{{ $item->id }}"
                                                        @checked($item->is_featured != null)>
                                                </div>
                                            </td>
                                            <td class="status">
                                                @if ($item->status)
                                                    <span
                                                        class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                                @else
                                                    <span
                                                        class="badge bg-danger-subtle text-danger text-uppercase">Inactive</span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="d-flex gap-1">
                                                    <div class="edit">
                                                        <form action="{{ route('admin.promotions.edit', $item->id) }}"
                                                            method="get">
                                                            @csrf
                                                            <button class="btn btn-sm btn-success edit-item-btn"
                                                                data-bs-toggle="modal" data-bs-target="#showModal">
                                                                <i class="las la-eye-dropper"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="show">
                                                        <form action="{{ route('admin.promotions.show', $item->id) }}"
                                                            method="get">
                                                            @csrf
                                                            <button class="btn btn-sm btn-primary show-item-btn"
                                                                data-bs-toggle="modal" data-bs-target="#showModal">
                                                                <i class="las la-eye"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="remove">
                                                        <form method="POST"
                                                            action="{{ route('admin.promotions.destroy', $item->id) }}"
                                                            class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger remove-item-btn btn-delete"
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
                                @if ($promotions->onFirstPage())
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0);">Previous</a>
                                @else
                                    <a class="page-item pagination-prev"
                                        href="{{ $promotions->previousPageUrl() }}">Previous</a>
                                @endif

                                {{-- Các số trang --}}
                                <ul class="pagination listjs-pagination mb-0">
                                    @foreach ($promotions->getUrlRange(1, $promotions->lastPage()) as $page => $url)
                                        <li class="page-item {{ $page == $promotions->currentPage() ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Nút Next --}}
                                @if ($promotions->hasMorePages())
                                    <a class="page-item pagination-next" href="{{ $promotions->nextPageUrl() }}">Next</a>
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
