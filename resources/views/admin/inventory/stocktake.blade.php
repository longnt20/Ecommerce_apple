{{-- resources/views/admin/inventory/stocktake.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Kiểm kê kho')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="ri-file-list-3-line me-2"></i>Kiểm kê kho hàng
            </h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.dashboard') }}">Kho hàng</a></li>
                    <li class="breadcrumb-item active">Kiểm kê</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-soft-primary">
                <h5 class="card-title mb-0">
                    <i class="ri-filter-3-line me-2"></i>Bộ lọc kiểm kê
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.inventory.stocktake') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Chọn kho kiểm kê</label>
                            <select name="warehouse_id" id="warehouseSelect" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Tất cả kho --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" 
                                        {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }} ({{ $warehouse->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Tất cả danh mục --</option>
                                @foreach(\App\Models\Category::all() as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" 
                                placeholder="Tìm theo SKU, tên sản phẩm..." 
                                value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i>Lọc
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-2">Tổng SKU cần kiểm</p>
                        <h4 class="mb-0">{{ $inventory->total() }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-primary-subtle">
                            <i class="ri-barcode-box-line avatar-title fs-24 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-2">Đã kiểm kê</p>
                        <h4 class="mb-0 text-success" id="checkedCount">0</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-success-subtle">
                            <i class="ri-checkbox-circle-line avatar-title fs-24 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-2">Có chênh lệch</p>
                        <h4 class="mb-0 text-warning" id="differenceCount">0</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-warning-subtle">
                            <i class="ri-alert-line avatar-title fs-24 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-2">Tiến độ</p>
                        <h4 class="mb-0">
                            <span id="progressPercent">0</span>%
                        </h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-info-subtle">
                            <i class="ri-percent-line avatar-title fs-24 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Tiến độ kiểm kê</h6>
                    <span class="text-muted">
                        <span id="currentProgress">0</span> / {{ $inventory->total() }} mặt hàng
                    </span>
                </div>
                <div class="progress progress-lg">
                    <div class="progress-bar bg-success" id="progressBar" role="progressbar" 
                        style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stocktake Form -->
<form id="stocktakeForm" action="{{ route('admin.inventory.stocktake.process') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-clipboard-line me-2"></i>Bảng kiểm kê
                        </h5>
                        <div>
                            <button type="button" class="btn btn-info me-2" onclick="startScanning()">
                                <i class="ri-qr-scan-2-line me-1"></i>Quét mã vạch
                            </button>
                            <button type="button" class="btn btn-warning me-2" onclick="quickCount()">
                                <i class="ri-speed-line me-1"></i>Kiểm nhanh
                            </button>
                            <button type="button" class="btn btn-success me-2" onclick="saveProgress()">
                                <i class="ri-save-line me-1"></i>Lưu tiến độ
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-check-double-line me-1"></i>Hoàn tất kiểm kê
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                        </div>
                                    </th>
                                    <th width="10%">SKU</th>
                                    <th width="25%">Sản phẩm</th>
                                    <th width="10%">Kho</th>
                                    <th width="8%">Vị trí</th>
                                    <th width="10%" class="text-center">Tồn hệ thống</th>
                                    <th width="12%" class="text-center">Tồn thực tế</th>
                                    <th width="10%" class="text-center">Chênh lệch</th>
                                    <th width="10%">Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventory as $item)
                                <tr id="row-{{ $item->id }}" class="stocktake-row">
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input row-check" type="checkbox" 
                                                value="{{ $item->id }}">
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ $item->productVariant->sku }}</code>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('storage/' . $item->productVariant->product->thumbnail) }}" 
                                                alt="" class="avatar-sm rounded me-2">
                                            <div>
                                                <h6 class="mb-0 fs-14">
                                                    {{ $item->productVariant->product->name }}
                                                </h6>
                                                <small class="text-muted">
                                                    {{ $item->productVariant->color }} - {{ $item->productVariant->storage }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-info text-info">
                                            {{ $item->warehouse->name }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $item->location ?: '-' }}
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="adjustments[{{ $loop->index }}][inventory_id]" 
                                            value="{{ $item->id }}">
                                        <input type="hidden" id="system-qty-{{ $item->id }}" 
                                            value="{{ $item->quantity }}">
                                        <span class="badge bg-primary fs-6">
                                            {{ $item->quantity }}
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                            name="adjustments[{{ $loop->index }}][actual_quantity]" 
                                            id="actual-qty-{{ $item->id }}"
                                            class="form-control text-center actual-quantity" 
                                            value="{{ $item->quantity }}"
                                            min="0" 
                                            data-id="{{ $item->id }}"
                                            data-system="{{ $item->quantity }}"
                                            onchange="calculateDifference({{ $item->id }})">
                                    </td>
                                    <td class="text-center">
                                        <span id="diff-{{ $item->id }}" class="badge bg-secondary fs-6">
                                            0
                                        </span>
                                    </td>
                                    <td>
                                        <input type="text" 
                                            name="adjustments[{{ $loop->index }}][note]" 
                                            class="form-control form-control-sm"
                                            placeholder="Ghi chú...">
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="ri-inbox-line" style="font-size: 3rem; color: #dee2e6;"></i>
                                            <p class="text-muted mt-3">Không có sản phẩm nào để kiểm kê</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $inventory->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-file-text-line me-2"></i>Ghi chú kiểm kê
                    </h5>
                </div>
                <div class="card-body">
                    <textarea name="stocktake_notes" class="form-control" rows="4" 
                        placeholder="Ghi chú về đợt kiểm kê này..."></textarea>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Người kiểm kê</label>
                            <input type="text" name="counted_by" class="form-control" 
                                value="{{ auth()->user()->name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày kiểm kê</label>
                            <input type="datetime-local" name="counted_at" class="form-control" 
                                value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 text-white">
                        <i class="ri-calculator-line me-2"></i>Tổng kết kiểm kê
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td>Tổng mặt hàng:</td>
                                <td class="text-end fw-semibold">{{ $inventory->total() }}</td>
                            </tr>
                            <tr>
                                <td>Đã kiểm:</td>
                                <td class="text-end fw-semibold text-success">
                                    <span id="totalChecked">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Chưa kiểm:</td>
                                <td class="text-end fw-semibold text-warning">
                                    <span id="totalUnchecked">{{ $inventory->total() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Thừa:</td>
                                <td class="text-end fw-semibold text-info">
                                    <span id="totalSurplus">0</span> sản phẩm
                                </td>
                            </tr>
                            <tr>
                                <td>Thiếu:</td>
                                <td class="text-end fw-semibold text-danger">
                                    <span id="totalShortage">0</span> sản phẩm
                                </td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Tổng chênh lệch:</strong></td>
                                <td class="text-end">
                                    <strong id="totalDifference">0</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-3 d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ri-check-double-line me-1"></i>
                            Xác nhận điều chỉnh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Barcode Scanner Modal -->
<div class="modal fade" id="scannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quét mã vạch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div id="scanner-container" style="width: 100%; height: 300px;">
                        <!-- Scanner will be initialized here -->
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hoặc nhập mã vạch thủ công:</label>
                    <input type="text" id="manualBarcode" class="form-control" 
                        placeholder="Nhập mã vạch và nhấn Enter">
                </div>
                <div id="scanResult" class="alert alert-info d-none">
                    <strong>Kết quả:</strong> <span id="scannedCode"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.stocktake-row.checked {
    background-color: #f0f8ff;
}

.stocktake-row.has-difference {
    background-color: #fff3cd;
}

.actual-quantity {
    width: 100px;
    margin: 0 auto;
}

.actual-quantity:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
}

.badge.positive-diff {
    background-color: #28a745 !important;
}

.badge.negative-diff {
    background-color: #dc3545 !important;
}

.table-hover tbody tr:hover {
    background-color: #f5f5f5;
    cursor: pointer;
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
let checkedItems = new Set();
let differences = {};

// Calculate difference for each item
function calculateDifference(id) {
    const systemQty = parseInt(document.getElementById(`system-qty-${id}`).value);
    const actualQty = parseInt(document.getElementById(`actual-qty-${id}`).value) || 0;
    const difference = actualQty - systemQty;
    
    // Update difference display
    const diffElement = document.getElementById(`diff-${id}`);
    diffElement.textContent = difference > 0 ? `+${difference}` : difference;
    
    // Update styling based on difference
    if (difference > 0) {
        diffElement.className = 'badge bg-success fs-6';
        document.getElementById(`row-${id}`).classList.add('has-difference');
    } else if (difference < 0) {
        diffElement.className = 'badge bg-danger fs-6';
        document.getElementById(`row-${id}`).classList.add('has-difference');
    } else {
        diffElement.className = 'badge bg-secondary fs-6';
        document.getElementById(`row-${id}`).classList.remove('has-difference');
    }
    
    // Store difference
    differences[id] = difference;
    
    // Mark as checked
    markAsChecked(id);
    
    // Update summary
    updateSummary();
}

// Mark item as checked
function markAsChecked(id) {
    checkedItems.add(id);
    document.getElementById(`row-${id}`).classList.add('checked');
    updateProgress();
}

// Update progress
function updateProgress() {
    const total = {{ $inventory->total() }};
    const checked = checkedItems.size;
    const percentage = total > 0 ? Math.round((checked / total) * 100) : 0;
    
    document.getElementById('checkedCount').textContent = checked;
    document.getElementById('currentProgress').textContent = checked;
    document.getElementById('progressPercent').textContent = percentage;
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressBar').setAttribute('aria-valuenow', percentage);
    
    // Update difference count
    let diffCount = 0;
    for (let diff of Object.values(differences)) {
        if (diff !== 0) diffCount++;
    }
    document.getElementById('differenceCount').textContent = diffCount;
}

// Update summary
function updateSummary() {
    const total = {{ $inventory->total() }};
    const checked = checkedItems.size;
    const unchecked = total - checked;
    
    let surplus = 0;
    let shortage = 0;
    let totalDiff = 0;
    
    for (let diff of Object.values(differences)) {
        if (diff > 0) surplus += diff;
        if (diff < 0) shortage += Math.abs(diff);
        totalDiff += diff;
    }
    
    document.getElementById('totalChecked').textContent = checked;
    document.getElementById('totalUnchecked').textContent = unchecked;
    document.getElementById('totalSurplus').textContent = surplus;
    document.getElementById('totalShortage').textContent = shortage;
    document.getElementById('totalDifference').textContent = 
        totalDiff > 0 ? `+${totalDiff}` : totalDiff;
}

// Check all functionality
document.getElementById('checkAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-check');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
        if (this.checked) {
            const id = cb.value;
            markAsChecked(id);
        }
    });
});

// Individual checkbox
document.querySelectorAll('.row-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const id = this.value;
        if (this.checked) {
            markAsChecked(id);
        } else {
            checkedItems.delete(id);
            document.getElementById(`row-${id}`).classList.remove('checked');
            updateProgress();
        }
    });
});

// Quick count - set all to 0
function quickCount() {
    if (confirm('Đặt tất cả số lượng thực tế = 0?')) {
        document.querySelectorAll('.actual-quantity').forEach(input => {
            input.value = 0;
            calculateDifference(input.dataset.id);
        });
    }
}

// Save progress
function saveProgress() {
    const formData = new FormData(document.getElementById('stocktakeForm'));
    formData.append('save_progress', '1');
    
    fetch('{{ route("admin.inventory.stocktake.process") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('Đã lưu tiến độ kiểm kê!');
        }
    })
    .catch(error => {
        toastr.error('Có lỗi xảy ra!');
    });
}

// Start barcode scanning
function startScanning() {
    const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
    modal.show();
}

// Manual barcode input
document.getElementById('manualBarcode')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const barcode = this.value;
        processBarcode(barcode);
        this.value = '';
    }
});

// Process scanned barcode
function processBarcode(barcode) {
    // Find product by SKU
    const row = document.querySelector(`[data-sku="${barcode}"]`);
    if (row) {
        const id = row.dataset.id;
        const input = document.getElementById(`actual-qty-${id}`);
        const currentQty = parseInt(input.value) || 0;
        input.value = currentQty + 1;
        calculateDifference(id);
        
        document.getElementById('scannedCode').textContent = barcode;
        document.getElementById('scanResult').classList.remove('d-none');
        
        // Highlight row
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('table-warning');
        setTimeout(() => {
            row.classList.remove('table-warning');
        }, 2000);
    } else {
        alert('Không tìm thấy sản phẩm với mã: ' + barcode);
    }
}

// Form submission
document.getElementById('stocktakeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (checkedItems.size === 0) {
        alert('Vui lòng kiểm kê ít nhất một sản phẩm!');
        return false;
    }
    
    if (confirm(`Xác nhận điều chỉnh tồn kho cho ${checkedItems.size} mặt hàng?`)) {
        this.submit();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S: Save progress
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        saveProgress();
    }
    
    // Ctrl + Enter: Submit form
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('stocktakeForm').submit();
    }
});

// Auto-save every 5 minutes
setInterval(function() {
    if (checkedItems.size > 0) {
        saveProgress();
    }
}, 300000); // 5 minutes

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateProgress();
    updateSummary();
});
</script>
@endpush