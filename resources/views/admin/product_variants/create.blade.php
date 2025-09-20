@extends('admin.layouts.app')
@section('title', 'Thêm mới biến thể sản phẩm')

@push('styles')
    <style>
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1.2rem;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .form-control,
        .form-select {
            border: 1.5px solid #e0e6ed;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .image-upload-container {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .image-upload-container:hover {
            border-color: #667eea;
            background: #f0f3ff;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
        }

        .product-selector {
            background: #f8f9fa;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-info {
            flex: 1;
        }

        .product-info .label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .product-info .value {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .table {
            font-size: 0.9rem;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 1rem;
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .search-box input {
            padding-left: 3rem;
        }

        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .info-box i {
            color: #667eea;
            margin-right: 0.5rem;
        }

        #selected-product-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e0e6ed;
        }

        #selected-product-info:hover {
            background: #f0f3ff;
            border-color: #667eea;
            transition: all 0.3s ease;
        }

        #empty-product-state {
            background: #fafbfc;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
    </style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box d-sm-flex align-items-center justify-content-between bg-white rounded-3 p-4 mb-4 shadow-sm">
                <div>
                    <h4 class="mb-2 fw-bold">Thêm mới biến thể sản phẩm</h4>
                    <p class="text-muted mb-0">Quản lý và tạo các biến thể cho sản phẩm của bạn</p>
                </div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.product_variants.index') }}">Quản lí biến thể</a>
                        </li>
                        <li class="breadcrumb-item active">Thêm mới</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form id="variant-form" method="POST" action="{{ route('admin.product_variants.store') }}"
        enctype="multipart/form-data">
        @csrf
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Product Selection Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-shopping-bag-line me-2"></i>Sản phẩm gốc</h5>
                    </div>
                    <div class="card-body">
                        <!-- Before Selection State -->
                        <div id="empty-product-state" class="text-center py-4">
                            <div class="mb-3">
                                <i class="ri-shopping-bag-line" style="font-size: 3rem; color: #dee2e6;"></i>
                            </div>
                            <p class="text-muted mb-3">Chưa chọn sản phẩm nào</p>
                            <button type="button" class="btn btn-gradient" data-bs-toggle="modal"
                                data-bs-target="#productModal">
                                <i class="ri-add-line me-1"></i>Chọn sản phẩm
                            </button>
                        </div>

                        <!-- After Selection State -->
                        <div id="selected-product-info" class="d-none">
                            <input type="hidden" name="product_id" id="product_id" required>
                            <div class="d-flex align-items-center gap-3">
                                <div class="flex-shrink-0">
                                    <img id="selected-product-image" src="" alt="" class="rounded"
                                        style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #e0e6ed;">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" id="selected-product-name">Tên sản phẩm</h6>
                                    <p class="mb-1 text-muted small">
                                        <span>Danh mục: <span id="selected-product-category"
                                                class="fw-medium">-</span></span>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#productModal">
                                        <i class="ri-exchange-line me-1"></i>Đổi sản phẩm
                                    </button>
                                </div>
                            </div>
                        </div>

                        @error('product_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Variant Information Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-settings-3-line me-2"></i>Thông tin biến thể</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Mã SKU <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-barcode-line"></i></span>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                        name="sku" placeholder="VD: IP15P-256-BLK" value="{{ old('sku') }}">
                                </div>
                                @error('sku')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Barcode
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-barcode-line"></i></span>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                        name="barcode" value="{{ old('barcode') }}">
                                </div>
                                @error('barcode')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Giá bán <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₫</span>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror"
                                        name="price" placeholder="31,000,000" value="{{ old('price') }}">
                                </div>
                                @error('price')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Giá khuyến mãi
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₫</span>
                                    <input type="number" class="form-control" name="cost_price"
                                        placeholder="28,000,000" value="{{ old('cost_price') }}">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        @inject('attributeService', 'App\Services\ProductAttributeService')

                        <h6 class="mb-3 fw-bold">Thuộc tính biến thể</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="ri-palette-line me-1"></i>Màu sắc <span class="required">*</span>
                                </label>
                                <select name="color" class="form-select @error('color') is-invalid @enderror">
                                    <option value="">-- Chọn màu sắc --</option>
                                    @foreach ($attributeService->getColors() as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('color') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('color')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="ri-hard-drive-2-line me-1"></i>Dung lượng <span class="required">*</span>
                                </label>
                                <select name="storage" class="form-select @error('storage') is-invalid @enderror">
                                    <option value="">-- Chọn dung lượng --</option>
                                    @foreach ($attributeService->getStorages() as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('storage') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('storage')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Image Upload Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-image-line me-2"></i>Hình ảnh biến thể</h5>
                    </div>
                    <div class="card-body">
                        <div class="image-upload-container"
                            onclick="document.getElementById('product-image-input').click();">
                            <input class="form-control d-none" id="product-image-input" type="file" name="thumbnail"
                                accept="image/png, image/gif, image/jpeg, image/webp">

                            <div id="image-preview-container" style="display: none;">
                                <div class="image-preview">
                                    <img src="" id="product-img" class="img-fluid" />
                                </div>
                                <p class="mt-3 mb-0 text-muted">Nhấp để thay đổi</p>
                            </div>

                            <div id="upload-placeholder">
                                <i class="ri-upload-cloud-2-line" style="font-size: 3rem; color: #667eea;"></i>
                                <p class="mt-3 mb-0">Kéo thả hoặc nhấp để tải ảnh lên</p>
                                <small class="text-muted">PNG, JPG, GIF (Tối đa 2MB)</small>
                            </div>
                        </div>
                        @error('thumbnail')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Status Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-toggle-line me-2"></i>Trạng thái</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                            <label class="form-check-label" for="status">
                                Kích hoạt biến thể này
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-gradient w-100 mb-2">
                            <i class="ri-save-line me-1"></i>Lưu biến thể
                        </button>
                        <a href="{{ route('admin.product_variants.index') }}" class="btn btn-light w-100">
                            <i class="ri-arrow-left-line me-1"></i>Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Product Selection Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">
                        <i class="ri-shopping-bag-line me-2"></i>Chọn sản phẩm
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="search-box mb-4">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchProduct" class="form-control"
                            placeholder="Tìm kiếm theo tên sản phẩm, SKU...">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60">Ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th width="100">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="productList">
                                @foreach ($products as $product)
                                    <tr>
                                        <td>
                                            <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                                alt="{{ $product->name }}" class="rounded"
                                                style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $product->name }}</div>
                                        </td>
                                        <td>{{ $product->category->name ?? '-' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-gradient select-product"
                                                data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                data-image="{{ asset('storage/' . $product->thumbnail) }}"
                                                data-category="{{ $product->category->name ?? '-' }}">
                                                <i class="ri-check-line me-1"></i>Chọn
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
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Enhanced Product Search
            const searchInput = document.getElementById("searchProduct");
            if (searchInput) {
                searchInput.addEventListener("keyup", function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll("#productList tr");

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? "" : "none";
                    });
                });
            }

            // Enhanced Product Selection with Image Display
            document.querySelectorAll(".select-product").forEach(button => {
                button.addEventListener("click", function() {
                    // Get product data from button attributes
                    const productData = {
                        id: this.dataset.id,
                        name: this.dataset.name,
                        image: this.dataset.image,
                        category: this.dataset.category,
                    };

                    // Update hidden input
                    document.getElementById("product_id").value = productData.id;

                    // Update product display
                    document.getElementById("selected-product-name").textContent = productData.name;
                    document.getElementById("selected-product-image").src = productData.image;
                    document.getElementById("selected-product-category").textContent = productData
                        .category;

                    // Show selected product info and hide empty state
                    document.getElementById("empty-product-state").classList.add("d-none");
                    document.getElementById("selected-product-info").classList.remove("d-none");

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById(
                        'productModal'));
                    modal.hide();

                    // Show success notification
                    showNotification('Đã chọn sản phẩm: ' + productData.name, 'success');
                });
            });

            // Check if product is already selected (for edit mode)
            const productId = document.getElementById("product_id").value;
            if (productId) {
                // If editing, show the selected product info
                document.getElementById("empty-product-state").classList.add("d-none");
                document.getElementById("selected-product-info").classList.remove("d-none");
            }

            // Enhanced Image Preview
            const imageInput = document.getElementById("product-image-input");
            const imagePreview = document.getElementById("product-img");
            const previewContainer = document.getElementById("image-preview-container");
            const uploadPlaceholder = document.getElementById("upload-placeholder");

            if (imageInput) {
                imageInput.addEventListener("change", function() {
                    const file = this.files[0];

                    if (file) {
                        // Check file size (2MB max)
                        if (file.size > 2 * 1024 * 1024) {
                            showNotification('File ảnh không được vượt quá 2MB', 'error');
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            previewContainer.style.display = 'block';
                            uploadPlaceholder.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.src = "";
                        previewContainer.style.display = 'none';
                        uploadPlaceholder.style.display = 'block';
                    }
                });
            }

            // Form validation
            const form = document.getElementById('variant-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const productId = document.getElementById('product_id').value;

                    if (!productId) {
                        e.preventDefault();
                        showNotification('Vui lòng chọn sản phẩm trước khi lưu', 'error');

                        // Scroll to product selection
                        document.getElementById("empty-product-state").scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Add shake animation
                        document.querySelector(".card").classList.add("shake");
                        setTimeout(() => {
                            document.querySelector(".card").classList.remove("shake");
                        }, 500);

                        return false;
                    }
                });
            }
        });

        // Notification function
        function showNotification(message, type = 'info') {
            // Create toast notification
            const toastHTML = `
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
            <div class="toast show align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', toastHTML);

            // Auto remove after 3 seconds
            setTimeout(() => {
                document.querySelector('.toast').remove();
            }, 3000);
        }

        // Add shake animation CSS
        const style = document.createElement('style');
        style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }
    .shake {
        animation: shake 0.5s;
    }
`;
        document.head.appendChild(style);
    </script>
@endpush
