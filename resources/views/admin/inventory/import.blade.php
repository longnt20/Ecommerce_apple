{{-- resources/views/admin/inventory/import.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Nhập kho')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">
                    <i class="ri-download-2-line me-2"></i>Nhập kho hàng
                </h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.inventory.dashboard') }}">Kho hàng</a></li>
                        <li class="breadcrumb-item active">Nhập kho</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form id="importForm" action="{{ route('admin.inventory.import.store') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Basic Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin nhập kho</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kho nhập <span class="text-danger">*</span></label>
                                <select name="warehouse_id" class="form-select" required>
                                    <option value="">Chọn kho</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">
                                            {{ $warehouse->name }} ({{ $warehouse->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ngày nhập</label>
                                <input type="date" name="import_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nhà cung cấp</label>
                                <input type="text" name="supplier" class="form-control" placeholder="Tên nhà cung cấp">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mã phiếu nhập</label>
                                <input type="text" name="reference_code" class="form-control" placeholder="PO-2024-001">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Items -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Chi tiết sản phẩm nhập</h5>
                            <div class="">
                                <button type="button" class="btn btn-primary btn-sm" onclick="addProductRow()">
                                    <i class="ri-add-line me-1"></i>Thêm thủ công
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#bulkVariantModal">
                                    <i class="ri-stack-line me-1"></i>Thêm tự dộng
                                </button>
                            </div>

                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="productTable">
                                <thead>
                                    <tr>
                                        <th width="40%">Sản phẩm</th>
                                        <th width="15%">Số lượng</th>
                                        <th width="20%">Giá nhập</th>
                                        <th width="20%">Thành tiền</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="productItems">
                                    <tr class="product-row">
                                        <td>
                                            <select name="items[0][product_variant_id]" class="form-select product-select"
                                                required>
                                                <option value="">Chọn sản phẩm</option>
                                                @foreach ($variants as $variant)
                                                    <option value="{{ $variant->id }}"
                                                        data-price="{{ $variant->cost_price }}">
                                                        {{ $variant->product->name }} -
                                                        {{ $variant->sku }}
                                                        ({{ $variant->color }} - {{ $variant->storage }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][quantity]"
                                                class="form-control quantity-input" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][unit_cost]"
                                                class="form-control price-input" min="0" step="1000">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control total-input" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Tổng cộng:</td>
                                        <td>
                                            <input type="text" id="grandTotal" class="form-control fw-bold" readonly
                                                value="0">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                            <div class="modal fade" id="bulkVariantModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title">Nhập nhiều biến thể</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            {{-- Chọn sản phẩm --}}
                            <div class="mb-3">
                                <label class="form-label">Sản phẩm</label>
                                <select id="bulkProduct" class="form-select">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Danh sách biến thể --}}
                            <div class="mb-3">
                                <label class="form-label">Biến thể</label>
                                <div id="variantList" class="border rounded p-2"
                                    style="max-height: 250px; overflow-y: auto;">
                                    <div class="text-muted text-center">
                                        Vui lòng chọn sản phẩm
                                    </div>
                                </div>
                            </div>

                            {{-- Số lượng chung --}}
                            <div class="mb-3">
                                <label class="form-label">Số lượng áp dụng cho tất cả</label>
                                <input type="number" id="bulkQuantity" class="form-control" min="1">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="button" class="btn btn-primary" onclick="applyBulkVariants()">
                                Thêm vào bảng
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Notes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ghi chú</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="5" placeholder="Ghi chú về phiếu nhập..."></textarea>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin tóm tắt</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tổng số mặt hàng:</span>
                            <strong id="totalItems">0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tổng số lượng:</span>
                            <strong id="totalQuantity">0</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tổng giá trị:</span>
                            <strong id="totalValue">0 ₫</strong>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="import" class="btn btn-success">
                                <i class="ri-check-line me-1"></i>Xác nhận nhập kho
                            </button>
                            <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                <i class="ri-save-line me-1"></i>Lưu nháp
                            </button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i>Hủy bỏ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('styles')
    <style>
        .product-row td {
            vertical-align: middle;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let rowIndex = 1;

        function addProductRow() {
            const tbody = document.getElementById('productItems');
            const newRow = document.createElement('tr');
            newRow.className = 'product-row';
            newRow.innerHTML = `
        <td>
            <select name="items[${rowIndex}][product_variant_id]" 
                class="form-select product-select" required>
                <option value="">Chọn sản phẩm</option>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}" 
                        data-price="{{ $variant->cost_price }}">
                        {{ $variant->product->name }} -  
                        {{ $variant->color }} - {{ $variant->storage }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[${rowIndex}][quantity]" 
                class="form-control quantity-input" 
                min="1" required>
        </td>
        <td>
            <input type="number" name="items[${rowIndex}][unit_cost]" 
                class="form-control price-input" 
                min="0" step="1000">
        </td>
        <td>
            <input type="text" class="form-control total-input" readonly>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" 
                onclick="removeRow(this)">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
            tbody.appendChild(newRow);
            rowIndex++;
            attachEventListeners();
        }

        function removeRow(button) {
            const row = button.closest('tr');
            if (document.querySelectorAll('.product-row').length > 1) {
                row.remove();
                calculateTotals();
            }
        }

        function attachEventListeners() {
            // Product select change
            document.querySelectorAll('.product-select').forEach(select => {
                select.removeEventListener('change', handleProductChange);
                select.addEventListener('change', handleProductChange);
            });

            // Quantity and price input
            document.querySelectorAll('.quantity-input, .price-input').forEach(input => {
                input.removeEventListener('input', calculateRowTotal);
                input.addEventListener('input', calculateRowTotal);
            });
        }

        function handleProductChange(e) {
            const select = e.target;
            const row = select.closest('tr');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.dataset.price || 0;

            const priceInput = row.querySelector('.price-input');
            priceInput.value = price;

            calculateRowTotal.call(priceInput);
        }

        function calculateRowTotal() {
            const row = this.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const total = quantity * price;

            row.querySelector('.total-input').value = formatNumber(total);

            calculateTotals();
        }

        function calculateTotals() {
            let totalItems = 0;
            let totalQuantity = 0;
            let totalValue = 0;

            document.querySelectorAll('.product-row').forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;

                if (quantity > 0) {
                    totalItems++;
                    totalQuantity += quantity;
                    totalValue += quantity * price;
                }
            });

            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('totalQuantity').textContent = formatNumber(totalQuantity);
            document.getElementById('totalValue').textContent = formatNumber(totalValue) + ' ₫';
            document.getElementById('grandTotal').value = formatNumber(totalValue);
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            attachEventListeners();
        });

        // Form validation
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const warehouse = document.querySelector('[name="warehouse_id"]').value;
            if (!warehouse) {
                e.preventDefault();
                alert('Vui lòng chọn kho nhập!');
                return false;
            }

            const hasProducts = document.querySelectorAll('.product-select').length > 0;
            if (!hasProducts) {
                e.preventDefault();
                alert('Vui lòng thêm ít nhất một sản phẩm!');
                return false;
            }
        });
    </script>
    <script>
        let variantCache = {}

        document.getElementById('bulkProduct').addEventListener('change', function() {
            const productId = this.value
            const container = document.getElementById('variantList')

            if (!productId) {
                container.innerHTML = '<div class="text-muted text-center">Vui lòng chọn sản phẩm</div>'
                return
            }

            if (variantCache[productId]) {
                renderVariants(variantCache[productId])
                return
            }

            fetch(`http://127.0.0.1:8000/api/products/${productId}/variants`)
                .then(res => res.json())
                .then(data => {
                    variantCache[productId] = data
                    renderVariants(data)
                })
        })

        function renderVariants(variants) {
            const container = document.getElementById('variantList')
            container.innerHTML = ''

            variants.forEach(v => {
                container.innerHTML += `
            <div class="form-check">
                <input class="form-check-input variant-check"
                    type="checkbox"
                    value="${v.id}"
                    data-price="${v.cost_price}">
                <label class="form-check-label">
                    ${v.color} - ${v.storage}
                </label>
            </div>
        `
            })
        }
    </script>
    <script>
        function addRowByVariant(variantId, label, price = 0, quantity = 1) {
            const tbody = document.getElementById('productItems');

            const tr = document.createElement('tr');
            tr.className = 'product-row';

            tr.innerHTML = `
        <td>
            <input type="hidden"
                name="items[${rowIndex}][product_variant_id]"
                value="${variantId}">
            ${label}
        </td>
        <td>
            <input type="number"
                name="items[${rowIndex}][quantity]"
                class="form-control quantity-input"
                min="1"
                value="${quantity}" required>
        </td>
        <td>
            <input type="number"
                name="items[${rowIndex}][unit_cost]"
                class="form-control price-input"
                min="0" step="1000"
                value="${price}">
        </td>
        <td>
            <input type="text"
                class="form-control total-input"
                readonly>
        </td>
        <td>
            <button type="button"
                class="btn btn-danger btn-sm"
                onclick="removeRow(this)">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;

            tbody.appendChild(tr);
            rowIndex++;

            attachEventListeners();
            calculateRowTotal.call(tr.querySelector('.quantity-input'));
        }
    </script>

    <script>
        function applyBulkVariants() {
            const quantity = document.getElementById('bulkQuantity').value;
            if (!quantity || quantity <= 0) {
                alert('Vui lòng nhập số lượng');
                return;
            }

            const checked = document.querySelectorAll('.variant-check:checked');
            if (!checked.length) {
                alert('Vui lòng chọn biến thể');
                return;
            }

            checked.forEach(cb => {
                const variantId = cb.value;
                const price = cb.dataset.price || 0;
                const label = cb.nextElementSibling.innerText;

                // Chống trùng biến thể
                const exists = document.querySelector(
                    `input[name$="[product_variant_id]"][value="${variantId}"]`
                );
                if (exists) return;

                addRowByVariant(variantId, label, price, quantity);
            });

            bootstrap.Modal.getInstance(
                document.getElementById('bulkVariantModal')
            ).hide();
        }
    </script>
@endpush
