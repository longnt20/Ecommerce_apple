{{-- resources/views/admin/inventory/export.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Xuất kho')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="ri-upload-2-line me-2"></i>Xuất kho hàng
            </h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.dashboard') }}">Kho hàng</a></li>
                    <li class="breadcrumb-item active">Xuất kho</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<form id="exportForm" action="{{ route('admin.inventory.export.store') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card">
                <div class="card-header bg-soft-danger">
                    <h5 class="card-title mb-0">
                        <i class="ri-information-line me-2"></i>Thông tin xuất kho
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kho xuất <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="warehouseSelect" class="form-select" required>
                                <option value="">Chọn kho</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }} ({{ $warehouse->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày xuất</label>
                            <input type="date" name="export_date" class="form-control" 
                                value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loại xuất kho</label>
                            <select name="reference_type" class="form-select">
                                <option value="order">Đơn hàng</option>
                                <option value="return_supplier">Trả nhà cung cấp</option>
                                <option value="damage">Hư hỏng</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mã tham chiếu</label>
                            <input type="text" name="reference_code" class="form-control" 
                                placeholder="Mã đơn hàng, phiếu trả...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Người nhận</label>
                            <input type="text" name="recipient" class="form-control" 
                                placeholder="Tên khách hàng hoặc đơn vị nhận">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Items -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-package-2-line me-2"></i>Chi tiết sản phẩm xuất
                        </h5>
                        <button type="button" class="btn btn-primary btn-sm" 
                            onclick="openProductModal()" id="addProductBtn" disabled>
                            <i class="ri-add-line me-1"></i>Thêm sản phẩm
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="35%">Sản phẩm</th>
                                    <th width="15%" class="text-center">Tồn kho</th>
                                    <th width="15%" class="text-center">Có thể xuất</th>
                                    <th width="15%" class="text-center">SL xuất</th>
                                    <th width="15%">Lý do</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="productItems">
                                <tr id="emptyRow">
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                                        Chưa có sản phẩm nào. Vui lòng chọn kho và thêm sản phẩm.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Stock Alert -->
            <div class="card border-warning d-none" id="stockAlert">
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Cảnh báo:</strong> Một số sản phẩm có tồn kho thấp!
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="card">
                <div class="card-header bg-soft-info">
                    <h5 class="card-title mb-0">
                        <i class="ri-calculator-line me-2"></i>Thông tin tóm tắt
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tổng mặt hàng:</span>
                        <strong id="totalItems">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tổng số lượng:</span>
                        <strong id="totalQuantity">0</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Giá trị xuất kho:</span>
                        <strong id="totalValue" class="text-danger">0 ₫</strong>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-file-text-line me-2"></i>Ghi chú
                    </h5>
                </div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="4" 
                        placeholder="Ghi chú về phiếu xuất..."></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="ri-check-double-line me-1"></i>Xác nhận xuất kho
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="printExport()">
                            <i class="ri-printer-line me-1"></i>In phiếu xuất
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

<!-- Product Selection Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn sản phẩm xuất kho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" id="searchProduct" class="form-control" 
                            placeholder="Tìm kiếm theo tên, SKU...">
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-hover">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Sản phẩm</th>
                                <th>SKU</th>
                                <th class="text-center">Tồn kho</th>
                                <th class="text-center">Có thể xuất</th>
                                <th class="text-center">Đã giữ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productList">
                            <!-- Products will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.table td {
    vertical-align: middle;
}

.product-row:hover {
    background-color: #f8f9fa;
}

.stock-badge {
    font-size: 0.875rem;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
let selectedProducts = [];
let warehouseInventory = {};
let rowIndex = 0;

// Warehouse change handler
document.getElementById('warehouseSelect').addEventListener('change', function() {
    const warehouseId = this.value;
    
    if (warehouseId) {
        document.getElementById('addProductBtn').disabled = false;
        loadWarehouseInventory(warehouseId);
    } else {
        document.getElementById('addProductBtn').disabled = true;
        clearProductItems();
    }
});

// Load warehouse inventory
function loadWarehouseInventory(warehouseId) {
    fetch(`/admin/api/warehouse/${warehouseId}/inventory`)
        .then(response => response.json())
        .then(data => {
            warehouseInventory = data;
        })
        .catch(error => {
            console.error('Error loading inventory:', error);
            alert('Không thể tải danh sách sản phẩm!');
        });
}

// Open product modal
function openProductModal() {
    const warehouseId = document.getElementById('warehouseSelect').value;
    if (!warehouseId) {
        alert('Vui lòng chọn kho xuất!');
        return;
    }
    
    updateProductModal();
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

// Update product modal content
function updateProductModal() {
    const tbody = document.getElementById('productList');
    tbody.innerHTML = '';
    
    if (Object.keys(warehouseInventory).length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="text-muted">
                        <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                        Không có sản phẩm nào trong kho này
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    for (const [productId, inventory] of Object.entries(warehouseInventory)) {
        if (!selectedProducts.includes(productId) && inventory.available_quantity > 0) {
            const row = `
                <tr class="product-row">
                    <td>
                        <img src="${inventory.image}" class="avatar-sm rounded">
                    </td>
                    <td>
                        <div>
                            <h6 class="mb-0">${inventory.name}</h6>
                            <small class="text-muted">${inventory.variant}</small>
                        </div>
                    </td>
                    <td><code>${inventory.sku}</code></td>
                    <td class="text-center">
                        <span class="badge bg-info">${inventory.quantity}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success">${inventory.available_quantity}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-warning">${inventory.reserved_quantity || 0}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                            onclick="addProduct('${productId}')">
                            <i class="ri-add-line"></i> Chọn
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        }
    }
}

// Add product to export list
function addProduct(productId) {
    const inventory = warehouseInventory[productId];
    
    if (!inventory) {
        alert('Không tìm thấy thông tin sản phẩm!');
        return;
    }
    
    selectedProducts.push(productId);
    
    const tbody = document.getElementById('productItems');
    if (selectedProducts.length === 1) {
        tbody.innerHTML = ''; // Clear empty row
    }
    
    const row = document.createElement('tr');
    row.id = `product-${productId}`;
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[${rowIndex}][product_variant_id]" value="${productId}">
            <div class="d-flex align-items-center">
                <img src="${inventory.image}" class="avatar-sm rounded me-2">
                <div>
                    <h6 class="mb-0">${inventory.name}</h6>
                    <small class="text-muted">${inventory.variant}</small>
                </div>
            </div>
        </td>
        <td class="text-center">
            <span class="badge bg-info">${inventory.quantity}</span>
        </td>
        <td class="text-center">
            <span class="badge bg-success">${inventory.available_quantity}</span>
        </td>
        <td>
            <input type="number" name="items[${rowIndex}][quantity]" 
                class="form-control quantity-input" 
                min="1" max="${inventory.available_quantity}" 
                value="1" onchange="updateSummary()" required>
        </td>
        <td>
            <input type="text" name="items[${rowIndex}][reason]" 
                class="form-control form-control-sm" 
                placeholder="Lý do xuất...">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" 
                onclick="removeProduct('${productId}')">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    rowIndex++;
    
    updateSummary();
    checkStockLevels();
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
}

// Remove product from export list
function removeProduct(productId) {
    selectedProducts = selectedProducts.filter(id => id !== productId);
    document.getElementById(`product-${productId}`).remove();
    
    if (selectedProducts.length === 0) {
        clearProductItems();
    }
    
    updateSummary();
    checkStockLevels();
}

// Clear product items
function clearProductItems() {
    selectedProducts = [];
    document.getElementById('productItems').innerHTML = `
        <tr id="emptyRow">
            <td colspan="6" class="text-center py-4 text-muted">
                <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                Chưa có sản phẩm nào. Vui lòng chọn kho và thêm sản phẩm.
            </td>
        </tr>
    `;
    updateSummary();
    document.getElementById('stockAlert').classList.add('d-none');
}

// Update summary
function updateSummary() {
    const quantities = document.querySelectorAll('.quantity-input');
    let totalItems = quantities.length;
    let totalQuantity = 0;
    let totalValue = 0;
    
    quantities.forEach((input, index) => {
        const qty = parseInt(input.value) || 0;
        totalQuantity += qty;
        
        // Calculate value
        const productId = selectedProducts[index];
        if (warehouseInventory[productId]) {
            totalValue += qty * (warehouseInventory[productId].unit_cost || 0);
        }
    });
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalQuantity').textContent = totalQuantity.toLocaleString('vi-VN');
    document.getElementById('totalValue').textContent = totalValue.toLocaleString('vi-VN') + ' ₫';
}

// Check stock levels
function checkStockLevels() {
    let hasLowStock = false;
    
    selectedProducts.forEach(productId => {
        const inventory = warehouseInventory[productId];
        if (inventory && inventory.available_quantity < 10) {
            hasLowStock = true;
        }
    });
    
    const alertDiv = document.getElementById('stockAlert');
    if (hasLowStock) {
        alertDiv.classList.remove('d-none');
    } else {
        alertDiv.classList.add('d-none');
    }
}

// Search products in modal
document.getElementById('searchProduct').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#productList tr.product-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Form validation
document.getElementById('exportForm').addEventListener('submit', function(e) {
    const warehouse = document.getElementById('warehouseSelect').value;
    
    if (!warehouse) {
        e.preventDefault();
        alert('Vui lòng chọn kho xuất!');
        return false;
    }
    
    if (selectedProducts.length === 0) {
        e.preventDefault();
        alert('Vui lòng thêm ít nhất một sản phẩm!');
        return false;
    }
    
    // Validate quantities
    let valid = true;
    document.querySelectorAll('.quantity-input').forEach((input, index) => {
        const qty = parseInt(input.value) || 0;
        const max = parseInt(input.max) || 0;
        
        if (qty > max) {
            valid = false;
            input.classList.add('is-invalid');
            alert(`Số lượng xuất không được vượt quá số lượng có thể xuất (${max})`);
        }
    });
    
    if (!valid) {
        e.preventDefault();
        return false;
    }
});

// Print export
function printExport() {
    window.print();
}
</script>
@endpush