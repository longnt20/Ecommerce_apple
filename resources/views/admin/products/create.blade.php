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
                                                    accept="image/png, image/gif, image/jpeg, image/webp">
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
                                    <input hidden class="form-control" type="file" id="gallery" name="gallery[]"
                                        multiple>
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
                                <h5 class="card-title mb-0">Biến thể</h5>
                            </div>
                            <div class="card-body">
                                <div id="variant-section" class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold text-primary mb-0">📦 Biến thể sản phẩm</h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            id="add-variant-btn">
                                            <i class="bi bi-plus-circle"></i> Thêm biến thể
                                        </button>
                                    </div>

                                    <div class="table-responsive shadow-sm rounded-3">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th style="width: 12%">SKU</th>
                                                    <th style="width: 10%">Giá bán</th>
                                                    <th style="width: 10%">Giá nhập</th>
                                                    <th style="width: 12%">Màu sắc</th>
                                                    <th style="width: 14%">Bộ nhớ</th>
                                                    <th style="width: 10%">Ảnh</th>
                                                    <th style="width: 5%">Xóa</th>
                                                </tr>
                                            </thead>
                                            <tbody id="variant-body" class="align-middle text-center text-secondary">
                                                <tr>
                                                    <td colspan="7" class="py-4 text-muted">Chưa có biến thể nào — hãy
                                                        nhấn <strong>“Thêm biến thể”</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @inject('attributeService', 'App\Services\ProductAttributeService')
                                <!-- Template để clone -->
                                <template id="variant-template">
                                    <tr class="variant-row">
                                        <td><input type="text" name="variants[][sku]"
                                                class="form-control form-control-sm" placeholder="Mã SKU" required></td>
                                        <td><input type="number" name="variants[][price]"
                                                class="form-control form-control-sm" step="0.01" placeholder="0.00"
                                                required></td>
                                        <td><input type="number" name="variants[][cost_price]"
                                                class="form-control form-control-sm" step="0.01" placeholder="0.00">
                                        </td>
                                        <td>
                                            <select name="variants[][color]"
                                                class="form-select @error('color') is-invalid @enderror">
                                                <option value="">-- Chọn màu sắc --</option>
                                                @foreach ($attributeService->getColors() as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ old('variants[][color]') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('color')
                                                <div class="text-danger mt-2">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="variants[][storage]"
                                                class="form-select @error('variants[][storage]') is-invalid @enderror">
                                                <option value="">-- Chọn dung lượng --</option>
                                                @foreach ($attributeService->getStorages() as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ old('variants[][storage]') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="position-relative d-inline-block">
                                                    <div class="position-absolute top-100 start-100 translate-middle">
                                                        <label for="variant-image-input" class="mb-0"
                                                            data-bs-toggle="tooltip" data-bs-placement="right"
                                                            title="Select Image">
                                                            <div class="avatar-xs">
                                                                <div
                                                                    class="avatar-title bg-light border rounded-circle text-muted cursor-pointer">
                                                                    <i class="ri-image-fill"></i>
                                                                </div>
                                                            </div>
                                                        </label>
                                                        <input class="form-control d-none variant-image-input"
                                                            value="" type="file" name="variants[][thumbnail]"
                                                            accept="image/png, image/gif, image/jpeg, image/webp">
                                                    </div>
                                                    <div class="avatar-lg">
                                                        <div class="avatar-title bg-light rounded variant-img">
                                                            <img src="" class="avatar-md h-auto" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger remove-variant-btn"
                                                title="Xóa biến thể">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <style>
                                    #variant-section table th {
                                        font-size: 0.9rem;
                                        font-weight: 600;
                                    }

                                    #variant-section table td input {
                                        min-width: 100px;
                                    }
                                </style>
                            </div>
                            <!-- end card body -->
                        </div>
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
            const productInput = document.getElementById("product-image-input");
            const productPreview = document.getElementById("product-img");

            if (productInput) {
                productInput.addEventListener("change", function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = e => productPreview.src = e.target.result;
                        reader.readAsDataURL(file);
                    } else {
                        productPreview.src = "";
                    }
                });
            }
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('add-variant-btn');
            const variantBody = document.getElementById('variant-body');
            const template = document.getElementById('variant-template').innerHTML;

            let variantIndex = 0; // 💡 Biến đếm số dòng biến thể

            addBtn.addEventListener('click', () => {
                // Nếu là dòng "chưa có biến thể" thì xóa nó trước khi thêm dòng mới
                if (variantBody.children.length === 1 && variantBody.children[0].querySelector(
                        'td[colspan]')) {
                    variantBody.innerHTML = '';
                }

                // 💡 Thay thế tất cả 'variants[][' thành 'variants[<index>]['
                const newRowHTML = template.replaceAll('variants[][', `variants[${variantIndex}][`);

                // Tạo phần tử DOM từ HTML đã thay thế
                const newRow = document.createElement('tr');
                newRow.innerHTML = newRowHTML;

                // Thêm dòng mới vào bảng
                variantBody.appendChild(newRow);

                variantIndex++; // Tăng chỉ số
            });

            variantBody.addEventListener("change", function(e) {
                if (e.target.classList.contains("variant-image-input")) {
                    const file = e.target.files[0];
                    // tìm ảnh trong cùng hàng
                    const imgElement = e.target.closest('.position-relative').querySelector(
                        '.variant-img img');

                    if (file && imgElement) {
                        const reader = new FileReader();
                        reader.onload = function(evt) {
                            imgElement.src = evt.target.result; // ✅ gán src cho thẻ img
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
            variantBody.addEventListener('click', function(e) {
                if (e.target.closest('.remove-variant-btn')) {
                    e.target.closest('tr').remove();

                    // Nếu xóa hết thì thêm dòng thông báo lại
                    if (variantBody.children.length === 0) {
                        variantBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-4 text-muted">Chưa có biến thể nào — hãy nhấn <strong>“Thêm biến thể”</strong></td>
                    </tr>`;
                    }
                }
            });
        });
        document.addEventListener('click', function(e) {
            if (e.target.closest('.ri-image-fill')) {
                const container = e.target.closest('.position-relative');
                const input = container.querySelector('.variant-image-input');
                if (input) input.click();
            }
        });
    </script>
@endpush
