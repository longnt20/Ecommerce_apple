@extends('admin.layouts.app')
@section('title', 'Thêm mới sản phẩm')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Quản lí sản phẩm</a></li>
                        <li class="breadcrumb-item active">Thêm mới sản phẩm</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Thêm mới sản phẩm</h4>
        </div><!-- end card header -->
        <div class="card-body">
            <form id="product-form" method="POST" action="{{ route('admin.products.store') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-7">
                        <div class="card" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Thông tin chung</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label" for="product-title-input">Tên sản phẩm</label>
                                    <input type="text" class="form-control" name="name" id="product-title-input"
                                        value="{{ old('name') }}" placeholder="Nhập tên sản phẩm">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="product-title-input">Giá mặc định</label>
                                    <input type="text" class="form-control" name="default_price" id="product-title-input"
                                        value="{{ old('default_price') }}" placeholder="Nhập giá sản phẩm">
                                </div>
                                <div>
                                    <label>Mô tả ngắn sản phẩm</label>
                                    <textarea class="form-control" name="short_description" rows="6">{{ old('short_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <!-- end card -->
                        <div class="card" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Thông số kỹ thuật</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <table class="table table-bordered" id="spec-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%">Tên thông số</th>
                                                <th style="width: 40%">Giá trị</th>
                                                <th style="width: 20%">Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="text" name="specs[0][name]" class="form-control">
                                                </td>
                                                <td><input type="text" name="specs[0][value]" class="form-control">
                                                </td>
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm btn-remove">Xóa</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button type="button" id="add-spec" class="btn btn-primary btn-sm">+ Thêm thông
                                        số</button>
                                </div>


                            </div>
                            <!-- end card body -->
                        </div>


                        <!-- end card -->
                    </div>
                    <!-- end col -->

                    <div class="col-lg-5">
                        <div class="card" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Ảnh sản phẩm</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="fs-14 mb-1">Ảnh chính</h5>
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
                                <div>
                                    <h5 class="fs-14 mb-1">Bộ sưu tập ảnh</h5>
                                    <input hidden class="form-control" type="file" id="gallery" name="gallery[]" multiple>
                                    <!-- Nút bấm giống Dropzone -->
                                    <div id="dropzone-mock" class="border border-2 rounded p-4 text-center"
                                        style="cursor: pointer;">
                                        <i class="ri-upload-cloud-2-fill display-4 text-muted"></i>
                                        <h6>Kéo & thả hoặc bấm để chọn ảnh</h6>
                                    </div>

                                    <!-- Preview ảnh -->
                                    <div id="preview-container" class="d-flex flex-wrap mt-3 gap-2"></div>
                                </div>

                            </div>

                        </div>
                        <div class="card" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Xuất bản</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="choices-publish-status-input" class="form-label">Trạng thái</label>

                                    <select class="form-select" id="choices-publish-status-input" name="status">
                                        <option value="published" selected>Published</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="choices-publish-visibility-input" class="form-label">Trạng thái hiển
                                        thị</label>
                                    <select class="form-select" name="visibility">
                                        <option value="public" selected>Public</option>
                                        <option value="hidden">Hidden</option>
                                    </select>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                        <div class="card" style="border-width: 2px;">
                            <div class="card-header" style="background-color:aliceblue">
                                <h5 class="card-title mb-0">Danh mục</h5>
                            </div>
                            <div class="card-body">
                                <select class="form-select" id="choices-category-input" name="category_id">
                                    @foreach ($categories as $cate)
                                        <option value="{{ $cate->id }}">{{ $cate->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                        <!-- end card -->

                    </div>
                    <!-- end col -->
                    <div>
                        <div class="card" style="border-width: 2px;">
                        <div class="card-header" style="background-color:aliceblue">
                            <h5 class="card-title mb-0">Mô tả sản phẩm</h5>
                        </div>
                        <div class="card-body">
                            <textarea name="description" id="description" hidden></textarea>
                        </div>
                        <!-- end card body -->
                    </div>
                    </div>
                </div>
                <!-- end row -->
                <div class="text-end mb-3">
                    <button type="submit" class="btn btn-success w-sm">Submit</button>
                    {{-- <button type="button" onclick="console.log(new FormData(this.form));">Test Form</button> --}}
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        @once
        let myEditor;
        ClassicEditor.create(document.querySelector('#description'))
            .then(editor => {
                myEditor = editor;
            });

        document.querySelector('#product-form').addEventListener('submit', function() {
            document.querySelector('#description').value = myEditor.getData();
        });
        @endonce
    </script>
    <script>
        const input = document.getElementById("gallery");
        const dropzoneMock = document.getElementById("dropzone-mock");
        const previewContainer = document.getElementById("preview-container");
        
        // Khi click vùng dropzone -> mở file chọn
        dropzoneMock.addEventListener("click", () => input.click());

        // Render preview khi chọn file
        input.addEventListener("change", () => {
            previewContainer.innerHTML = ""; // clear cũ

            Array.from(input.files).forEach((file, index) => {
                if (!file.type.startsWith("image/")) return; // chỉ nhận ảnh

                const reader = new FileReader();
                reader.onload = (e) => {
                    const wrapper = document.createElement("div");
                    wrapper.classList.add("position-relative");
                    wrapper.style.width = "100px";
                    wrapper.style.height = "100px";

                    wrapper.innerHTML = `
                    <img src="${e.target.result}" class="img-fluid rounded" style="width:100%;height:100%;object-fit:cover;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-btn" data-index="${index}">×</button>
                `;

                    previewContainer.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        });

        // Xóa ảnh khỏi preview + input.files
        previewContainer.addEventListener("click", (e) => {
            if (e.target.classList.contains("remove-btn")) {
                const index = e.target.getAttribute("data-index");

                // Tạo lại FileList trừ file bị xóa
                const dt = new DataTransfer();
                Array.from(input.files).forEach((file, i) => {
                    if (i != index) dt.items.add(file);
                });
                input.files = dt.files;

                // Render lại preview
                input.dispatchEvent(new Event("change"));
            }
        });
    </script>
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
            let specIndex = 1;

            // nút thêm
            document.getElementById('add-spec').addEventListener('click', function() {
                let tbody = document.querySelector('#spec-table tbody');
                let row = document.createElement('tr');

                row.innerHTML = `
                <td><input type="text" name="specs[${specIndex}][name]" class="form-control"></td>
                <td><input type="text" name="specs[${specIndex}][value]" class="form-control"></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-remove">Xóa</button>
                </td>
            `;
                tbody.appendChild(row);
                specIndex++;
            });

            // nút xóa (event delegation)
            document.querySelector('#spec-table').addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remove')) {
                    e.target.closest('tr').remove();
                }
            });
        });
    </script>
@endpush
