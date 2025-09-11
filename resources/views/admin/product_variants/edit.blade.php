@extends('admin.layouts.app')
@section('title', 'Cập nhật biến thể sản phẩm')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí biến thể sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Quản lí biến thể sản phẩm</a></li>
                        <li class="breadcrumb-item active">Cập nhật biến thể sản phẩm</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Cập nhật biến thể sản phẩm</h4>
        </div><!-- end card header -->
        <div class="card-body">
            <form id="variant-form" method="POST" action="{{ route('admin.product_variants.update', $productVariant->id) }}"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-lg-7">

                        {{-- Chọn sản phẩm cha --}}
                        <div class="card mb-3" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Chọn sản phẩm</h5>
                            </div>
                            <div class="card-body">
                                <div class="input-group">
                                    {{-- Ẩn product_id --}}
                                    <input type="hidden" name="product_id" id="product_id"
                                        value="{{ old('product_id', $productVariant->product_id ?? '') }}">

                                    {{-- Hiển thị tên sản phẩm --}}
                                    <input type="text" id="product_name" class="form-control"
                                        placeholder="Chưa chọn sản phẩm" readonly
                                        value="{{ old('product_name', $productVariant->product->name ?? '') }}">

                                    {{-- Nếu muốn KHÓA không cho chọn lại sản phẩm khi edit --}}
                                    @if (isset($productVariant))
                                        <button type="button" class="btn btn-secondary" disabled>
                                            Đã chọn
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#productModal">
                                            Chọn sản phẩm
                                        </button>
                                    @endif
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
                                        value="{{ $productVariant->sku }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Giá bán</label>
                                    <input type="number" class="form-control" name="price"
                                        value="{{ $productVariant->price }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Giá khuyến mãi</label>
                                    <input type="number" class="form-control" name="cost_price"
                                        value="{{ $productVariant->cost_price }}">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Màu sắc</label>
                                        <select name="color" class="form-select">
                                            <option value="Black"
                                                {{ old('color', $productVariant->color) == 'Black' ? 'selected' : '' }}>
                                                Black</option>
                                            <option value="White"
                                                {{ old('color', $productVariant->color) == 'White' ? 'selected' : '' }}>
                                                White</option>
                                            <option value="Blue"
                                                {{ old('color', $productVariant->color) == 'Blue' ? 'selected' : '' }}>Blue
                                            </option>
                                            <option value="Natural Titanium"
                                                {{ old('color', $productVariant->color) == 'Natural Titanium' ? 'selected' : '' }}>
                                                Natural Titanium</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Dung lượng</label>
                                        <select name="storage" class="form-select">
                                            <option value="128GB"
                                                {{ old('storage', $productVariant->storage) == '128GB' ? 'selected' : '' }}>
                                                128GB</option>
                                            <option value="256GB"
                                                {{ old('storage', $productVariant->storage) == '256GB' ? 'selected' : '' }}>
                                                256GB</option>
                                            <option value="512GB"
                                                {{ old('storage', $productVariant->storage) == '512GB' ? 'selected' : '' }}>
                                                512GB</option>
                                            <option value="1TB"
                                                {{ old('storage', $productVariant->storage) == '1TB' ? 'selected' : '' }}>
                                                1TB</option>
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
                                                <input class="form-control d-none" value=""
                                                    id="product-image-input" type="file" name="thumbnail"
                                                    accept="image/png, image/gif, image/jpeg">
                                            </div>
                                            <div class="avatar-lg">
                                                <div class="avatar-title bg-light rounded">
                                                    <img src="{{ old('thumbnail', asset('storage/' . $productVariant->thumbnail)) }}"
                                                        id="product-img" class="avatar-md h-auto" />
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
                    <button type="submit" class="btn btn-success w-sm">Cập nhật biến thể</button>
                </div>
            </form>

        </div>
    </div>
@endsection
@push('scripts')
    {{-- Script --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
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
