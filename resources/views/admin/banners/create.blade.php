@extends('admin.layouts.app')
@push('page-css')
    <!-- plugin css -->
    <link href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush
@php
    $title = 'Thêm mới banner';
@endphp
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 ps-2">Quản lí banner</h4>

                <div class="page-title-right pe-3">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active"><a href="{{ route('admin.banners.index') }}">Danh sách
                                banner</a>
                        </li>
                        <li class="breadcrumb-item active"><a href="{{ route('admin.banners.create') }}">Thêm mới
                                banner</a>
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Thêm mới banner</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <form action="{{ route('admin.banners.store') }}" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Tiêu đề</label>
                                    <input type="text" name="title" class="form-control"
                                           placeholder="Vui lòng nhập tiêu đề..." value="{{ old('title') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hình ảnh</label>
                                    <input type="file" name="image" class="form-control" id="imageInput">
                                </div>
                                <div class="image-preview-container" style="display:none;">
                                    <img id="imagePreview" src="" alt="Image preview" class="img-fluid mt-2 w-25"/>
                                </div>
                                 <div class="mb-3">
                                <label class="form-label">Sản phẩm</label>
                                <select name="product_ids" class="form-select">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                                <div class="mb-3">
                                    <label class="form-label">Nội dung</label>
                                    <textarea name="content" placeholder="Nhập nội dung..." class="form-control"
                                              value="{{ old('content') }}"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select name="status" class="form-select" value="{{ old('status') }}">
                                        <option value="" selected>Chọn trạng thái</option>
                                        <option value="1">Active</option>
                                        <option value="0">InActive</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Tạo
                                        mới
                                    </button>
                                    <button type="reset" class="btn btn-soft-primary waves-effect waves-light">Nhập
                                        lại
                                    </button>
                                    <a class="btn btn-dark" href="{{ route('admin.banners.index') }}">Danh sách</a>
                                </div>
                            </form>
                        </div>
                    </div><!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end col -->
        </div>


    </div>
@endsection
@push('scripts')
    <script>
        // Lắng nghe sự kiện thay đổi khi người dùng chọn tệp
        document.getElementById("imageInput").addEventListener("change", function (event) {
            const file = event.target.files[0]; // Lấy tệp đã chọn

            if (file) {
                // Tạo URL cho tệp được chọn
                const reader = new FileReader();
                reader.onload = function (e) {
                    // Lấy URL của ảnh
                    const imageUrl = e.target.result;

                    // Cập nhật ảnh xem trước
                    const imagePreview = document.getElementById("imagePreview");
                    imagePreview.src = imageUrl;

                    // Hiển thị phần tử ảnh xem trước
                    document.querySelector(".image-preview-container").style.display = "block";
                };
                reader.readAsDataURL(file); // Đọc tệp ảnh dưới dạng URL
            } else {
                // Nếu không có tệp nào được chọn, ẩn ảnh xem trước
                document.querySelector(".image-preview-container").style.display = "none";
            }
        });
    </script>
@endpush
