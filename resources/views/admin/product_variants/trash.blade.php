@extends('admin.layouts.app')
@section('title', 'Danh sách biến thể sản phẩm đã xóa')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí biến thể sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí biến thể sản phẩm</a></li>
                        <li class="breadcrumb-item">Danh sách biến thể sản phẩm đã xóa</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Danh sách biến thể sản phẩm đã xóa</h4>

                </div><!-- end card header -->

                <div class="card-body">
                    <div class="listjs-table" id="customerList">
                        <div class="row g-4 mb-3">
                            <div class="col-sm-auto">
                                <div>
                                    <a href="{{ route('admin.products.create') }}" class="btn btn-success add-btn"><i
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
                                        <th data-sort="customer_id">Mã sản phẩm</th>
                                        <th data-sort="customer_name">Tên sản phẩm</th>
                                        <th data-sort="email">Ảnh</th>
                                        <th data-sort="cate">Giá</th>
                                        <th data-sort="phone">Giá khuyến mãi</th>
                                        <th data-sort="phone">Màu</th>
                                        <th data-sort="date">Dung lượng</th>
                                        <th data-sort="action">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach ($productVariantDeleted as $item)
                                        <tr>
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child"
                                                        value="{{ $item->id }}">
                                                </div>
                                            </th>
                                            <td class="customer_id">{{ $item->sku }}</td>
                                            <td class="customer_name">{{ $item->product->name }}</td>
                                            <td class="email">
                                                <img src="{{ asset('storage/' . $item->thumbnail) }}" alt=""
                                                    width="100px">
                                            </td>
                                            <td class="customer_name">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                            <td class="phone">{{ number_format($item->cost_price, 0, ',', '.') }} đ</td>
                                            <td class="customer_name">{{ $item->color }}</td>
                                            <td class="customer_name">{{ $item->storage }}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <div class="edit">
                                                        <form action="{{ route('admin.product_variants.restore', $item->id) }}"
                                                            method="post">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="btn btn-sm btn-success edit-item-btn btn-remove"
                                                                data-bs-toggle="modal" data-bs-target="#showModal">
                                                                Khôi phục
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="remove">
                                                        <form method="POST"
                                                            action="{{ route('admin.product_variants.force-delete', $item->id) }}"
                                                            class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger remove-item-btn btn-forcedelete"
                                                                data-name="{{ $item->product->name }}">
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
                                @if ($productVariantDeleted->onFirstPage())
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0);">Previous</a>
                                @else
                                    <a class="page-item pagination-prev"
                                        href="{{ $productVariantDeleted->previousPageUrl() }}">Previous</a>
                                @endif

                                {{-- Các số trang --}}
                                <ul class="pagination listjs-pagination mb-0">
                                    @foreach ($productVariantDeleted->getUrlRange(1, $productVariantDeleted->lastPage()) as $page => $url)
                                        <li
                                            class="page-item {{ $page == $productVariantDeleted->currentPage() ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Nút Next --}}
                                @if ($productVariantDeleted->hasMorePages())
                                    <a class="page-item pagination-next"
                                        href="{{ $productVariantDeleted->nextPageUrl() }}">Next</a>
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
