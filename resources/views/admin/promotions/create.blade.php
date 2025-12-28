@extends('admin.layouts.app')

@section('title', 'Tạo Chương Trình Khuyến Mãi')
@push('page-css')
    <style>
        .frame-item {
            background: #fff;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .06);
        }

        .frame-preview-mini {
            position: relative;
            height: 150px;
            background: #f3f3f3;
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
            top: 15px;
            left: 12px;
            width: 88%;
            height: 55px;
            z-index: 1;
        }

        .bg-bottom {
            left: 10px;
            top: 30px;
            width: 90%;
            height: 100px;
            z-index: 2;
        }

        .ribbon {
            width: 100%;
            height: 10%;
            top: 30px;
            left: 30%;
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            z-index: 5;
        }

        .decor-left {
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            left: 17px;
            top: 40px;
            width: 30px;
            z-index: 4;
            height: 30%;
        }

        .decor-right {
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            right: -13px;
            top: 40px;
            width: 30px;
            z-index: 4;
            height: 30%;
        }
        .bg-top-preview {
            left: 12px;
            width: 93%;
            height: 45px;
            z-index: 1;
        }

        .bg-bottom-preview {
            left: 10px;
            top: 30px;
            width: 95%;
            height: 120px;
            z-index: 2;
        }

        .ribbon-preview {
            width: 100%;
            height: 20%;
            right: 25%;
            z-index: 5;
        }

        .decor-left-preview {
            transform: translateX(-50%);
            left: 17px;
            top: 28px;
            width: 35px;
            z-index: 4;
            height: 30%;
        }

        .decor-right-preview {
            transform: translateX(-50%);
            right: -13px;
            top: 27px;
            width: 35px;
            z-index: 4;
            height: 30%;
        }
    </style>
@endpush
@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tạo Chương Trình Khuyến Mãi</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.promotions.store') }}" method="POST" id="promotionForm"
                    enctype="multipart/form-data">
                    @csrf
                    <!-- Thông tin cơ bản -->
                    <div class="form-section mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h5 class=" mb-3">Thông Tin Cơ Bản</h5>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="is_featured">
                                    Chương trình nổi bật
                                </label>
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                    value="1" {{ old('is_featured') ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Tên chương trình <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                        placeholder="VD: Flash Sale Tết 2024">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Danh mục</label>
                                    <select name="category_id"
                                        class="form-select @error('category_id') is-invalid @enderror">
                                        <option value="">-- Chọn danh mục --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" name="thumbnail"
                                    class="form-control @error('thumbnail') is-invalid @enderror"
                                    value="{{ old('thumbnail') }}" placeholder="URL hoặc đường dẫn ảnh">
                                @error('thumbnail')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <label class="form-label mt-2">Mô tả</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4"
                                    placeholder="Mô tả chi tiết về chương trình khuyến mãi">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <!-- Khung áp dụng -->
                                <div class="form-section">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">
                                            Khung áp dụng <span class="text-danger">*</span>
                                        </label>

                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#frameModal">
                                            Chọn khung
                                        </button>
                                    </div>

                                    <input type="hidden" name="frame_id" id="frame_id">

                                    {{-- Empty state --}}
                                    <div id="frameEmptyState" class="border rounded text-center text-muted py-4" style="height:160px;">
                                        <i class="fas fa-box-open fa-2x mb-2"></i>
                                        <div>Chưa chọn khung chương trình</div>
                                    </div>

                                    {{-- Selected frame preview --}}
                                    <div id="selectedFramePreview" class="card mt-2 d-none">
                                        <div class="card-body">
                                            <div class="frame-preview-mini mb-2">
                                                <div id="preview-top" class="bg-top-preview"></div>
                                                <div id="preview-bottom" class="bg-bottom-preview"></div>
                                                <div id="preview-ribbon" class="ribbon-preview"></div>
                                                <div id="preview-left" class="decor-left-preview"></div>
                                                <div id="preview-right" class="decor-right-preview"></div>
                                            </div>

                                            <div class="fw-semibold" id="selectedFrameName"></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- Modal -->
                        <div class="modal fade" id="frameModal" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">

                                    <div class="modal-header">
                                        <h5 class="modal-title">Chọn khung</h5>
                                        <button type="button" class="btn btn-sm btn-close"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="row">
                                            @foreach ($frames as $frame)
                                                <div class="col-md-4 mb-3">
                                                    <div class="card frame-item h-100">
                                                        <div class="card-body p-2">

                                                            <div class="frame-preview-mini mb-2">
                                                                @if ($frame->top_background)
                                                                    <div class="bg-top"
                                                                        style="background-image:url({{ Storage::url($frame->top_background) }})">
                                                                    </div>
                                                                @endif
                                                                @if ($frame->bottom_background)
                                                                    <div class="bg-bottom"
                                                                        style="background-image:url({{ Storage::url($frame->bottom_background) }})">
                                                                    </div>
                                                                @endif
                                                                @if ($frame->ribbon_image)
                                                                    <div class="ribbon"
                                                                        style="background-image:url({{ Storage::url($frame->ribbon_image) }})">
                                                                    </div>
                                                                @endif
                                                                @if ($frame->left_decor_image)
                                                                    <div class="decor-left"
                                                                        style="background-image:url({{ Storage::url($frame->left_decor_image) }})">
                                                                    </div>
                                                                @endif
                                                                @if ($frame->right_decor_image)
                                                                    <div class="decor-right"
                                                                        style="background-image:url({{ Storage::url($frame->right_decor_image) }})">
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <div class="small fw-semibold text-center mb-2">
                                                                {{ $frame->name }}
                                                            </div>

                                                            <button type="button"
                                                                class="btn btn-sm btn-primary w-100 btn-choose-frame"
                                                                data-id="{{ $frame->id }}"
                                                                data-name="{{ $frame->name }}"
                                                                data-top="{{ $frame->top_background ? Storage::url($frame->top_background) : '' }}"
                                                                data-bottom="{{ $frame->bottom_background ? Storage::url($frame->bottom_background) : '' }}"
                                                                data-ribbon="{{ $frame->ribbon_image ? Storage::url($frame->ribbon_image) : '' }}"
                                                                data-left="{{ $frame->left_decor_image ? Storage::url($frame->left_decor_image) : '' }}"
                                                                data-right="{{ $frame->right_decor_image ? Storage::url($frame->right_decor_image) : '' }}">
                                                                Chọn khung
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="start_date"
                                        class="form-control @error('start_date') is-invalid @enderror"
                                        value="{{ old('start_date') }}">
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="end_date"
                                        class="form-control @error('end_date') is-invalid @enderror"
                                        value="{{ old('end_date') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                                        <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Kích hoạt
                                        </option>
                                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Tạm dừng
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sản phẩm áp dụng -->
                    <div class="form-section mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Sản Phẩm Áp Dụng <span class="text-danger">*</span></h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#productModal">
                                <i class="fas fa-plus"></i> Chọn sản phẩm
                            </button>
                        </div>

                        <!-- Danh sách sản phẩm đã chọn -->
                        <div id="selectedProductsList" class="border rounded p-3">
                            <div id="emptyState" class="text-center text-muted py-4">
                                <i class="fas fa-box-open fa-3x mb-3"></i>
                                <p>Chưa có sản phẩm nào được chọn</p>
                            </div>

                            <!-- Container cho sản phẩm đã chọn -->
                            <div id="productsContainer" style="display: none;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tên sản phẩm</th>
                                            <th>Ảnh</th>
                                            <th>Giá</th>
                                            <th>Loại</th>
                                            <th width="80" class="text-center">
                                                Hành Động
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedProductsTable"></tbody>
                                </table>
                            </div>
                        </div>

                        @error('items')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.promotions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Tạo chương trình
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Chọn Sản Phẩm -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Chọn Sản Phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="base-products-tab" data-bs-toggle="tab"
                                data-bs-target="#base-products" type="button" role="tab">
                                <i class="fas fa-box"></i> Sản phẩm gốc
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="variant-products-tab" data-bs-toggle="tab"
                                data-bs-target="#variant-products" type="button" role="tab">
                                <i class="fas fa-layer-group"></i> Sản phẩm biến thể
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="productTabsContent">
                        <!-- Sản phẩm gốc -->
                        <div class="tab-pane fade show active" id="base-products" role="tabpanel">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="searchBaseProducts"
                                    placeholder="Tìm kiếm sản phẩm gốc...">
                            </div>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover">
                                    <thead class="sticky-top bg-white">
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" class="form-check-input" id="selectAllBase">
                                            </th>
                                            <th>Tên sản phẩm</th>
                                            <th>Ảnh</th>
                                            <th>Giá</th>
                                        </tr>
                                    </thead>
                                    <tbody id="baseProductsTable">
                                        @foreach ($baseProducts as $product)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="form-check-input product-checkbox"
                                                        data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                        data-type="product" data-model="{{ \App\Models\Product::class }}"
                                                        data-thumbnail="{{ Storage::url($product->thumbnail) }}"
                                                        data-price="{{ $product->default_price }}">
                                                </td>
                                                <td>{{ $product->name }}</td>
                                                <td>
                                                    <img src="{{ Storage::url($product->thumbnail) }}" alt=""
                                                        width="50px">
                                                </td>
                                                <td>{{ number_format($product->default_price) }}đ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sản phẩm biến thể -->
                        <div class="tab-pane fade" id="variant-products" role="tabpanel">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="searchVariantProducts"
                                    placeholder="Tìm kiếm sản phẩm biến thể...">
                            </div>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover">
                                    <thead class="sticky-top bg-white">
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" class="form-check-input" id="selectAllVariant">
                                            </th>
                                            <th>Sản phẩm gốc</th>
                                            <th>Ảnh</th>
                                            <th>Màu</th>
                                            <th>Dung lượng</th>
                                            <th>SKU</th>
                                            <th>Giá</th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantProductsTable">
                                        @foreach ($variantProducts as $variant)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="form-check-input product-checkbox"
                                                        data-id="{{ $variant->id }}" data-name="{{ $variant->name }}"
                                                        data-parent="{{ $variant->product->name ?? '' }}"
                                                        data-type="variant"
                                                        data-model="{{ \App\Models\ProductVariant::class }}"
                                                        data-thumbnail="{{ Storage::url($variant->thumbnail) }}"
                                                        data-price="{{ $variant->price }}">
                                                </td>
                                                <td>{{ $variant->product->name ?? 'N/A' }}</td>
                                                <td>
                                                    <img src="{{ Storage::url($variant->thumbnail) }}" alt=""
                                                        srcset="" width="50px">
                                                </td>
                                                <td>{{ $variant->color_label }}</td>
                                                <td>{{ $variant->storage }}</td>
                                                <td>{{ $variant->sku }}</td>
                                                <td>{{ number_format($variant->price) }}đ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="confirmProducts">
                        <i class="fas fa-check"></i> Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .section-title {
            color: #495057;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .required {
            color: #dc3545;
        }

        #selectedProductsList {
            min-height: 150px;
            background: #fff;
        }

        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedProducts = [];

            // Search base products
            document.getElementById('searchBaseProducts').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('#baseProductsTable tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });

            // Search variant products
            document.getElementById('searchVariantProducts').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('#variantProductsTable tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });

            // Select all base products
            document.getElementById('selectAllBase').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('#baseProductsTable .product-checkbox');
                checkboxes.forEach(cb => {
                    if (cb.closest('tr').style.display !== 'none') {
                        cb.checked = this.checked;
                    }
                });
            });

            // Select all variant products
            document.getElementById('selectAllVariant').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('#variantProductsTable .product-checkbox');
                checkboxes.forEach(cb => {
                    if (cb.closest('tr').style.display !== 'none') {
                        cb.checked = this.checked;
                    }
                });
            });

            // Confirm selected products
            document.getElementById('confirmProducts').addEventListener('click', function() {
                selectedProducts = [];
                const checkboxes = document.querySelectorAll('.product-checkbox:checked');

                checkboxes.forEach(cb => {
                    selectedProducts.push({
                        id: cb.dataset.id,
                        name: cb.dataset.name,
                        type: cb.dataset.type,
                        model: cb.dataset.model,
                        parent: cb.dataset.parent || null,
                        thumbnail: cb.dataset.thumbnail || '',
                        price: parseFloat(cb.dataset.price) || 0
                    });
                });

                renderSelectedProducts();

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                modal.hide();
            });

            // Format currency
            function formatCurrency(value) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(value);
            }

            // Render selected products
            function renderSelectedProducts() {
                const container = document.getElementById('productsContainer');
                const emptyState = document.getElementById('emptyState');
                const tableBody = document.getElementById('selectedProductsTable');

                if (selectedProducts.length === 0) {
                    container.style.display = 'none';
                    emptyState.style.display = 'block';
                    return;
                }

                container.style.display = 'block';
                emptyState.style.display = 'none';
                tableBody.innerHTML = '';

                selectedProducts.forEach((product, index) => {
                    const row = document.createElement('tr');
                    const displayName = product.type === 'variant' && product.parent ?
                        `${product.parent} - ${product.name}` :
                        product.name;

                    row.innerHTML = `
                <td>${displayName}</td>
                <td>
                    <img src="${product.thumbnail}" width="50px">
                </td>
                <td>${formatCurrency(product.price)}</td>
                <td>
                    <span class="badge bg-${product.type === 'product' ? 'primary' : 'info'}">
                        ${product.type === 'product' ? 'Sản phẩm gốc' : 'Biến thể'}
                    </span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProduct(${index})">
                       <i class="ri-delete-bin-2-line"></i>
                    </button>
                </td>
                <td style="display: none;">
                    <input type="hidden" name="items[${index}][item_id]" value="${product.id}">
                    <input type="hidden" name="items[${index}][item_type]" value="${product.model}">
                </td>
            `;
                    tableBody.appendChild(row);
                });
            }

            // Remove product
            window.removeProduct = function(index) {
                const removedProduct = selectedProducts.splice(index, 1)[0];
                renderSelectedProducts();

                // Uncheck corresponding checkbox
                const cb = document.querySelector(`.product-checkbox[data-id="${removedProduct.id}"]`);
                if (cb) cb.checked = false;
            };

            // Form validation
            document.getElementById('promotionForm').addEventListener('submit', function(e) {
                if (selectedProducts.length === 0) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất 1 sản phẩm!');
                }
            });
        });
    </script>
    <script>
        document.querySelectorAll('.btn-choose-frame').forEach(btn => {
            btn.addEventListener('click', function() {

                document.getElementById('frame_id').value = this.dataset.id;
                document.getElementById('selectedFrameName').innerText = this.dataset.name;

                setBg('preview-top', this.dataset.top);
                setBg('preview-bottom', this.dataset.bottom);
                setBg('preview-ribbon', this.dataset.ribbon);
                setBg('preview-left', this.dataset.left);
                setBg('preview-right', this.dataset.right);

                document.getElementById('frameEmptyState').classList.add('d-none');
                document.getElementById('selectedFramePreview').classList.remove('d-none');

                bootstrap.Modal.getInstance(
                    document.getElementById('frameModal')
                ).hide();
            });
        });

        function setBg(id, url) {
            const el = document.getElementById(id);
            if (url) {
                el.style.backgroundImage = `url(${url})`;
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }
    </script>
@endpush
