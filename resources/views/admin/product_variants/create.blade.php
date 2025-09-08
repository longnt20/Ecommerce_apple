@extends('admin.layouts.app')
@section('title', 'Thêm mới biến thể sản phẩm')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí biến thể sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Quản lí biến thể sản phẩm</a></li>
                        <li class="breadcrumb-item active">Thêm mới biến thể sản phẩm</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Thêm mới biến thể sản phẩm</h4>
        </div><!-- end card header -->
        <div class="card-body">
            <form id="variant-form" method="POST" action="{{ route('admin.product_variants.store') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-7">

                        {{-- Chọn sản phẩm cha --}}
                        <div class="card mb-3" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Chọn sản phẩm</h5>
                            </div>
                            <div class="card-body">
                                <div class="input-group">
                                    <input type="hidden" name="product_id" id="product_id">
                                    <input type="text" id="product_name" class="form-control"
                                        placeholder="Chưa chọn sản phẩm" readonly>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#productModal">
                                        Chọn sản phẩm
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Thông tin biến thể --}}
                        <div class="card mb-3" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Thông tin biến thể</h5>
                            </div>
                            <div class="card-body">

                                <div class="mb-3">
                                    <label class="form-label">SKU</label>
                                    <input type="text" class="form-control" name="sku"
                                        placeholder="VD: IP15P-256-BLK">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Giá bán</label>
                                    <input type="number" class="form-control" name="price" placeholder="31000000"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Giá khuyến mãi</label>
                                    <input type="number" class="form-control" name="cost_price" placeholder="28000000">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Màu sắc</label>
                                        <select name="color" class="form-select">
                                            <option value="Black">Black</option>
                                            <option value="White">White</option>
                                            <option value="Blue">Blue</option>
                                            <option value="Natural Titanium">Natural Titanium</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Dung lượng</label>
                                        <select name="storage" class="form-select">
                                            <option value="128GB">128GB</option>
                                            <option value="256GB">256GB</option>
                                            <option value="512GB">512GB</option>
                                            <option value="1TB">1TB</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    {{-- Ảnh biến thể --}}
                    <div class="col-lg-5">
                        <div class="card" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Ảnh biến thể</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="text-center">
                                        <div class="position-relative d-inline-block">
                                            <div class="position-absolute top-100 start-100 translate-middle">
                                                <label for="product-image-input" class="mb-0" data-bs-toggle="tooltip"
                                                    data-bs-placement="right" title="Select Image">
                                                    <div class="avatar-xs">
                                                        <div
                                                            class="avatar-title bg-light border rounded-circle text-muted cursor-pointer">
                                                            <i class="ri-image-fill"></i>
                                                        </div>
                                                    </div>
                                                </label>
                                                <input class="form-control d-none" value="" id="product-image-input"
                                                    type="file" name="thumbnail"
                                                    accept="image/png, image/gif, image/jpeg">
                                            </div>
                                            <div class="avatar-lg">
                                                <div class="avatar-title bg-light rounded">
                                                    <img src="" id="product-img" class="avatar-md h-auto" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success w-sm">Thêm biến thể</button>
                </div>
            </form>

            {{-- Modal chọn sản phẩm --}}
            <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productModalLabel">Chọn sản phẩm</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">

                            <input type="text" id="searchProduct" class="form-control mb-3"
                                placeholder="Tìm kiếm sản phẩm...">

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tên sản phẩm</th>
                                        <th>Ảnh</th>
                                        <th>Danh mục</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody id="productList">
                                    @foreach ($products as $product)
                                        <tr>
                                            <td>{{ $product->name }}</td>
                                            <td>
                                                <img src="{{ asset('storage/' . $product->thumbnail) }}" alt=""
                                                    width="50px">
                                            </td>
                                            <td>{{ $product->category->name ?? '-' }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-success select-product"
                                                    data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                                                    Chọn
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>




        </div>
    </div>
@endsection
@push('scripts')
    {{-- Script --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tìm kiếm sản phẩm
            document.getElementById("searchProduct").addEventListener("keyup", function() {
                let value = this.value.toLowerCase();
                document.querySelectorAll("#productList tr").forEach(function(row) {
                    row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
                });
            });

            // Chọn sản phẩm
            document.querySelectorAll(".select-product").forEach(function(button) {
                button.addEventListener("click", function() {
                    let id = this.dataset.id;
                    let name = this.dataset.name;

                    document.getElementById("product_id").value = id;
                    document.getElementById("product_name").value = name;

                    let modal = bootstrap.Modal.getInstance(document.getElementById(
                        'productModal'));
                    modal.hide();
                });
            });
            // preview image
            const input = document.getElementById("product-image-input");
            const preview = document.getElementById("product-img");

            input.addEventListener("change", function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        preview.src = e.target.result; // gán ảnh vào thẻ img
                    };

                    reader.readAsDataURL(file); // đọc file thành base64
                } else {
                    preview.src = ""; // nếu bỏ chọn thì reset preview
                }
            });
        });
    </script>
@endpush
