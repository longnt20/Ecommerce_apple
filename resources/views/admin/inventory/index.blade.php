{{-- resources/views/admin/inventory/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Danh sách tồn kho')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="ri-stack-line me-2"></i>Tồn kho hiện tại
            </h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.dashboard') }}">Kho hàng</a></li>
                    <li class="breadcrumb-item active">Tồn kho</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Kho hàng</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">Tất cả kho</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" 
                                    {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>
                                Còn hàng
                            </option>
                            <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>
                                Sắp hết hàng
                            </option>
                            <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>
                                Hết hàng
                            </option>
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
                            <i class="ri-search-line me-1"></i>Tìm kiếm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row">
    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Tổng SKU</p>
                        <h4 class="mb-0">{{ $inventory->total() }}</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="avatar-sm rounded-circle bg-primary align-self-center">
                            <span class="avatar-title">
                                <i class="ri-shopping-bag-3-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Tổng số lượng</p>
                        <h4 class="mb-0">{{ number_format($inventory->sum('quantity')) }}</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="avatar-sm rounded-circle bg-success align-self-center">
                            <span class="avatar-title">
                                <i class="ri-stack-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Sắp hết hàng</p>
                        <h4 class="mb-0 text-warning">{{ $inventory->where('quantity', '<=', 10)->count() }}</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="avatar-sm rounded-circle bg-warning align-self-center">
                            <span class="avatar-title">
                                <i class="ri-alert-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Hết hàng</p>
                        <h4 class="mb-0 text-danger">{{ $inventory->where('available_quantity', '<=', 0)->count() }}</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="avatar-sm rounded-circle bg-danger align-self-center">
                            <span class="avatar-title">
                                <i class="ri-error-warning-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title">Danh sách tồn kho</h4>
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-success me-2" onclick="exportExcel()">
                            <i class="ri-file-excel-2-line me-1"></i>Xuất Excel
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
                            <i class="ri-settings-3-line me-1"></i>Điều chỉnh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Sản phẩm</th>
                                <th>Kho</th>
                                <th>Vị trí</th>
                                <th class="text-center">Tồn kho</th>
                                <th class="text-center">Có thể bán</th>
                                <th class="text-center">Đã giữ</th>
                                <th class="text-center">Đang về</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inventory as $item)
                            <tr>
                                <td>
                                    <code>{{ $item->productVariant->sku }}</code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ asset('storage/' . $item->productVariant->product->thumbnail) }}" 
                                            alt="" class="avatar-xs rounded me-2">
                                        <div>
                                            <h6 class="mb-0">{{ $item->productVariant->product->name }}</h6>
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
                                    <span class="fw-semibold">{{ number_format($item->quantity) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-success">{{ number_format($item->available_quantity) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-warning">{{ number_format($item->reserved_quantity) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-info">{{ number_format($item->incoming_quantity) }}</span>
                                </td>
                                <td>
                                    @if($item->isOutOfStock())
                                        <span class="badge bg-danger">Hết hàng</span>
                                    @elseif($item->isLowStock())
                                        <span class="badge bg-warning">Sắp hết</span>
                                    @else
                                        <span class="badge bg-success">Còn hàng</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-sm dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                            <i class="ri-more-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                    onclick="viewHistory({{ $item->id }})">
                                                    <i class="ri-history-line me-2"></i>Lịch sử
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                    onclick="adjustStock({{ $item->id }})">
                                                    <i class="ri-settings-3-line me-2"></i>Điều chỉnh
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    onclick="transferStock({{ $item->id }})">
                                                    <i class="ri-arrow-left-right-line me-2"></i>Chuyển kho
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    onclick="setMinStock({{ $item->id }})">
                                                    <i class="ri-alert-line me-2"></i>Cài đặt mức tối thiểu
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="ri-inbox-line" style="font-size: 3rem; color: #dee2e6;"></i>
                                        <p class="text-muted mt-3">Không có dữ liệu tồn kho</p>
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

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.inventory.adjust') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Điều chỉnh tồn kho</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kho hàng</label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">Chọn kho</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sản phẩm</label>
                        <select name="product_variant_id" class="form-select" required>
                            <option value="">Chọn sản phẩm</option>
                            @foreach(\App\Models\ProductVariant::with('product')->get() as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->product->name }} - {{ $variant->sku }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số lượng thực tế</label>
                        <input type="number" name="actual_quantity" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do điều chỉnh</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu điều chỉnh</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function viewHistory(id) {
    // Open history modal or redirect to history page
    window.location.href = `/admin/inventory/transactions?inventory_id=${id}`;
}

function adjustStock(id) {
    // Pre-fill the adjust modal with item data
    $('#adjustModal').modal('show');
}

function transferStock(id) {
    // Redirect to transfer page with pre-selected item
    window.location.href = `/admin/inventory/transfer?inventory_id=${id}`;
}

function setMinStock(id) {
    // Open modal to set minimum stock level
    alert('Set minimum stock for item: ' + id);
}

function exportExcel() {
    window.location.href = '{{ route("admin.inventory.index") }}?export=excel&' + window.location.search.substring(1);
}
</script>
@endpush