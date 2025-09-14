{{-- resources/views/admin/inventory/transfer.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Chuyển kho')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="ri-arrow-left-right-line me-2"></i>Chuyển kho hàng
            </h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.dashboard') }}">Kho hàng</a></li>
                    <li class="breadcrumb-item active">Chuyển kho</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<form id="transferForm" action="{{ route('admin.inventory.transfer.store') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Transfer Info -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-soft-primary">
                    <h5 class="card-title mb-0">
                        <i class="ri-information-line me-2"></i>Thông tin chuyển kho
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Từ kho <span class="text-danger">*</span></label>
                            <select name="from_warehouse_id" id="fromWarehouse" class="form-select" required>
                                <option value="">Chọn kho nguồn</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }} ({{ $warehouse->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <i class="ri-arrow-right-line fs-2"></i>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Đến kho <span class="text-danger">*</span></label>
                            <select name="to_warehouse_id" id="toWarehouse" class="form-select" required>
                                <option value="">Chọn kho đích</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }} ({{ $warehouse->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Ngày chuyển</label>
                            <input type="datetime-local" name="transfer_date" class="form-control" 
                                value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Người phụ trách</label>
                            <input type="text" name="responsible_person" class="form-control" 
                                placeholder="Tên người phụ trách chuyển kho">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phương tiện vận chuyển</label>
                            <input type="text" name="transport_method" class="form-control" 
                                placeholder="Xe tải, container...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-package-2-line me-2"></i>Sản phẩm chuyển kho
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
                            <thead>
                                <tr>
                                    <th width="40%">Sản phẩm</th>
                                    <th width="15%" class="text-center">Tồn kho</th>
                                    <th width="15%" class="text-center">SL chuyển</th>
                                    <th width="25%">Ghi chú</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="transferItems">
                                <tr id="emptyRow">
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Chưa có sản phẩm nào. Vui lòng chọn kho nguồn và thêm sản phẩm.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary & Actions -->
        <div class="col-lg-4">
            <!-- Summary -->
            <div class="card">
                <div class="card-header bg-soft-info">
                    <h5 class="card-title mb-0">
                        <i class="ri-calculator-line me-2"></i>Tổng quan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td>Tổng mặt hàng:</td>
                                    <td class="text-end fw-semibold">
                                        <span id="totalItems">0</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tổng số lượng:</td>
                                    <td class="text-end fw-semibold">
                                        <span id="totalQuantity">0</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Ước tính giá trị:</td>
                                    <td class="text-end fw-semibold">
                                        <span id="totalValue">0 ₫</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                        placeholder="Ghi chú về phiếu chuyển kho..."></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="ri-check-double-line me-1"></i>Xác nhận chuyển kho
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="saveDraft()">
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

<!-- Product Selection Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn sản phẩm chuyển kho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="searchProduct" class="form-control" 
                        placeholder="Tìm kiếm sản phẩm...">
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th>SKU</th>
                                <th class="text-center">Tồn kho</th>
                                <th class="text-center">Có thể chuyển</th>
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
.product-item {
    cursor: pointer;
    transition: background-color 0.2s;
}
.product-item:hover {
    background-color: #f8f9fa;
}
</style>
@endpush

@push('scripts')
<script>
let selectedProducts = [];
let warehouseInventory = {};

// Warehouse change handlers
document.getElementById('fromWarehouse').addEventListener('change', function() {
    const fromId = this.value;
    const toId = document.getElementById('toWarehouse').value;
    
    if (fromId) {
        document.getElementById('addProductBtn').disabled = false;
        loadWarehouseInventory(fromId);
    } else {
        document.getElementById('addProductBtn').disabled = true;
        clearTransferItems();
    }
    
    // Validate warehouses are different
    if (fromId && toId && fromId === toId) {
        alert('Kho nguồn và kho đích phải khác nhau!');
        this.value = '';
    }
});

document.getElementById('toWarehouse').addEventListener('change', function() {
    const fromId = document.getElementById('fromWarehouse').value;
    const toId = this.value;
    
    if (fromId && toId && fromId === toId) {
        alert('Kho nguồn và kho đích phải khác nhau!');
        this.value = '';
    }
});

// Load warehouse inventory
function loadWarehouseInventory(warehouseId) {
    fetch(`/admin/api/warehouse/${warehouseId}/inventory`)
        .then(response => response.json())
        .then(data => {
            warehouseInventory = data;
            updateProductModal();
        });
}

// Open product modal
function openProductModal() {
    const fromWarehouse = document.getElementById('fromWarehouse').value;
    if (!fromWarehouse) {
        alert('Vui lòng chọn kho nguồn trước!');
        return;
    }
    
    updateProductModal();
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

// Update product modal content
function updateProductModal() {
    const tbody = document.getElementById('productList');
    tbody.innerHTML = '';
    
    for (const [productId, inventory] of Object.entries(warehouseInventory)) {
        if (inventory.available_quantity > 0) {
            const row = `
                <tr class="product-item">
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${inventory.image}" class="avatar-xs rounded me-2">
                            <div>
                                <h6 class="mb-0">${inventory.name}</h6>
                                <small class="text-muted">${inventory.variant}</small>
                            </div>
                        </div>
                    </td>
                    <td><code>${inventory.sku}</code></td>
                    <td class="text-center">${inventory.quantity}</td>
                    <td class="text-center">
                        <span class="badge bg-success">${inventory.available_quantity}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                            onclick="addProduct('${productId}')">
                            <i class="ri-add-line"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        }
    }
}

// Add product to transfer list
function addProduct(productId) {
    const inventory = warehouseInventory[productId];
    
    if (selectedProducts.includes(productId)) {
        alert('Sản phẩm này đã được thêm!');
        return;
    }
    
    selectedProducts.push(productId);
    
    const tbody = document.getElementById('transferItems');
    if (selectedProducts.length === 1) {
        tbody.innerHTML = ''; // Clear empty row
    }
    
    const row = document.createElement('tr');
    row.id = `product-${productId}`;
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[${productId}][product_variant_id]" value="${productId}">
            <div class="d-flex align-items-center">
                <img src="${inventory.image}" class="avatar-xs rounded me-2">
                <div>
                    <h6 class="mb-0">${inventory.name}</h6>
                    <small class="text-muted">${inventory.variant}</small>
                </div>
            </div>
        </td>
        <td class="text-center">
            <span class="badge bg-info">${inventory.available_quantity}</span>
        </td>
        <td>
            <input type="number" name="items[${productId}][quantity]" 
                class="form-control quantity-input" 
                min="1" max="${inventory.available_quantity}" 
                value="1" onchange="updateSummary()" required>
        </td>
        <td>
            <input type="text" name="items[${productId}][note]" 
                class="form-control" placeholder="Ghi chú...">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" 
                onclick="removeProduct('${productId}')">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    
    updateSummary();
    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
}

// Remove product from transfer list
function removeProduct(productId) {
    selectedProducts = selectedProducts.filter(id => id !== productId);
    document.getElementById(`product-${productId}`).remove();
    
    if (selectedProducts.length === 0) {
        clearTransferItems();
    }
    
    updateSummary();
}

// Clear transfer items
function clearTransferItems() {
    selectedProducts = [];
    document.getElementById('transferItems').innerHTML = `
        <tr id="emptyRow">
            <td colspan="5" class="text-center py-4 text-muted">
                Chưa có sản phẩm nào. Vui lòng chọn kho nguồn và thêm sản phẩm.
            </td>
        </tr>
    `;
    updateSummary();
}

// Update summary
function updateSummary() {
    const quantities = document.querySelectorAll('.quantity-input');
    let totalItems = quantities.length;
    let totalQuantity = 0;
    let totalValue = 0;
    
    quantities.forEach(input => {
        const qty = parseInt(input.value) || 0;
        totalQuantity += qty;
        
        // Calculate value if needed
        const productId = input.name.match(/items```math
(\d+)```/)[1];
        if (warehouseInventory[productId]) {
            totalValue += qty * (warehouseInventory[productId].unit_cost || 0);
        }
    });
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalQuantity').textContent = totalQuantity.toLocaleString('vi-VN');
    document.getElementById('totalValue').textContent = totalValue.toLocaleString('vi-VN') + ' ₫';
}

// Search products in modal
document.getElementById('searchProduct').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#productList tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Form validation
document.getElementById('transferForm').addEventListener('submit', function(e) {
    const fromWarehouse = document.getElementById('fromWarehouse').value;
    const toWarehouse = document.getElementById('toWarehouse').value;
    
    if (!fromWarehouse || !toWarehouse) {
        e.preventDefault();
        alert('Vui lòng chọn kho nguồn và kho đích!');
        return false;
    }
    
    if (fromWarehouse === toWarehouse) {
        e.preventDefault();
        alert('Kho nguồn và kho đích phải khác nhau!');
        return false;
    }
    
    if (selectedProducts.length === 0) {
        e.preventDefault();
        alert('Vui lòng thêm ít nhất một sản phẩm!');
        return false;
    }
});

// Save draft
function saveDraft() {
    const formData = new FormData(document.getElementById('transferForm'));
    formData.append('is_draft', '1');
    
    fetch('{{ route("admin.inventory.transfer.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã lưu nháp phiếu chuyển kho!');
        }
    });
}
</script>
@endpush