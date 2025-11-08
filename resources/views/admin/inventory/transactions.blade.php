{{-- resources/views/admin/inventory/transactions.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Lịch sử giao dịch kho')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="ri-history-line me-2"></i>Lịch sử giao dịch kho
            </h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.dashboard') }}">Kho hàng</a></li>
                    <li class="breadcrumb-item active">Lịch sử giao dịch</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bộ lọc tìm kiếm</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.inventory.transactions') }}">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Loại giao dịch</label>
                            <select name="type" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="import" {{ request('type') == 'import' ? 'selected' : '' }}>
                                    Nhập kho
                                </option>
                                <option value="export" {{ request('type') == 'export' ? 'selected' : '' }}>
                                    Xuất kho
                                </option>
                                <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>
                                    Chuyển kho
                                </option>
                                <option value="adjust" {{ request('type') == 'adjust' ? 'selected' : '' }}>
                                    Điều chỉnh
                                </option>
                                <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>
                                    Trả hàng
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2">
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

                        <div class="col-md-2">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="date_from" class="form-control" 
                                value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="date_to" class="form-control" 
                                value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" 
                                placeholder="Mã giao dịch, SKU..." 
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line"></i>
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
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Tổng giao dịch</p>
                        <h2 class="mt-2 ff-secondary fw-semibold">
                            {{ number_format($transactions->total()) }}
                        </h2>
                    </div>
                    <div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-info rounded-circle fs-2">
                                <i class="ri-exchange-line text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Nhập kho</p>
                        <h2 class="mt-2 ff-secondary fw-semibold text-success">
                            +{{ number_format($transactions->where('type', 'import')->sum('quantity')) }}
                        </h2>
                    </div>
                    <div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-success rounded-circle fs-2">
                                <i class="ri-download-2-line text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Xuất kho</p>
                        <h2 class="mt-2 ff-secondary fw-semibold text-danger">
                            -{{ number_format(abs($transactions->where('type', 'export')->sum('quantity'))) }}
                        </h2>
                    </div>
                    <div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-danger rounded-circle fs-2">
                                <i class="ri-upload-2-line text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Giá trị</p>
                        <h2 class="mt-2 ff-secondary fw-semibold">
                            {{ number_format($transactions->sum('total_cost')) }}₫
                        </h2>
                    </div>
                    <div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-warning rounded-circle fs-2">
                                <i class="ri-money-dollar-circle-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Danh sách giao dịch</h4>
                    <div>
                        <button type="button" class="btn btn-success" onclick="exportExcel()">
                            <i class="ri-file-excel-2-line me-1"></i>Xuất Excel
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã GD</th>
                                <th>Thời gian</th>
                                <th>Loại</th>
                                <th>Sản phẩm</th>
                                <th>Kho</th>
                                <th class="text-center">Trước GD</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-center">Sau GD</th>
                                <th>Người thực hiện</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                            <tr>
                                <td>
                                    <code>{{ $transaction->transaction_code }}</code>
                                </td>
                                <td>
                                    <div>
                                        {{ $transaction->created_at->format('d/m/Y') }}
                                        <small class="text-muted d-block">
                                            {{ $transaction->created_at->format('H:i:s') }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $typeConfig = [
                                            'import' => ['color' => 'success', 'icon' => 'download-2', 'label' => 'Nhập'],
                                            'export' => ['color' => 'danger', 'icon' => 'upload-2', 'label' => 'Xuất'],
                                            'transfer' => ['color' => 'info', 'icon' => 'arrow-left-right', 'label' => 'Chuyển'],
                                            'adjust' => ['color' => 'warning', 'icon' => 'settings-3', 'label' => 'Điều chỉnh'],
                                            'return' => ['color' => 'secondary', 'icon' => 'arrow-go-back', 'label' => 'Trả hàng'],
                                            'reserve' => ['color' => 'primary', 'icon' => 'lock', 'label' => 'Giữ hàng'],
                                            'release' => ['color' => 'success', 'icon' => 'lock-unlock', 'label' => 'Hủy giữ'],
                                        ];
                                        $config = $typeConfig[$transaction->type] ?? ['color' => 'secondary', 'icon' => 'question', 'label' => $transaction->type];
                                    @endphp
                                    <span class="badge bg-{{ $config['color'] }}">
                                        <i class="ri-{{ $config['icon'] }}-line me-1"></i>
                                        {{ $config['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ asset('storage/' . $transaction->productVariant->thumbnail) }}" 
                                            class="avatar-xs rounded me-2" alt="">
                                        <div>
                                            <h6 class="mb-0 fs-14">
                                                {{ Str::limit($transaction->productVariant->product->name, 30) }}
                                            </h6>
                                            <small class="text-muted">
                                                SKU: {{ $transaction->productVariant->sku }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($transaction->type == 'transfer')
                                        <div>
                                            <span class="badge bg-soft-danger text-danger">
                                                {{ $transaction->fromWarehouse->name }}
                                            </span>
                                            <i class="ri-arrow-right-line mx-1"></i>
                                            <span class="badge bg-soft-success text-success">
                                                {{ $transaction->toWarehouse->name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="badge bg-soft-info text-info">
                                            {{ $transaction->warehouse->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ number_format($transaction->before_quantity) }}
                                </td>
                                <td class="text-center">
                                    @if($transaction->quantity > 0)
                                        <span class="text-success fw-semibold">
                                            +{{ number_format($transaction->quantity) }}
                                        </span>
                                    @else
                                        <span class="text-danger fw-semibold">
                                            {{ number_format($transaction->quantity) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ number_format($transaction->after_quantity) }}
                                </td>
                                <td>
                                    @if($transaction->creator)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                    {{ substr($transaction->creator->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fs-13">{{ $transaction->creator->name }}</h6>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusConfig = [
                                            'pending' => ['color' => 'warning', 'label' => 'Chờ duyệt'],
                                            'approved' => ['color' => 'info', 'label' => 'Đã duyệt'],
                                            'completed' => ['color' => 'success', 'label' => 'Hoàn thành'],
                                            'cancelled' => ['color' => 'danger', 'label' => 'Đã hủy'],
                                        ];
                                        $status = $statusConfig[$transaction->status] ?? ['color' => 'secondary', 'label' => $transaction->status];
                                    @endphp
                                    <span class="badge bg-{{ $status['color'] }}-subtle text-{{ $status['color'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-soft-primary btn-sm" 
                                        onclick="viewDetail({{ $transaction->id }})">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="ri-inbox-line" style="font-size: 3rem; color: #dee2e6;"></i>
                                        <p class="text-muted mt-3">Không có giao dịch nào</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết giao dịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function viewDetail(id) {
    // Load transaction detail via AJAX
    fetch(`/admin/inventory/transactions/${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
}

function exportExcel() {
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'excel');
    window.location.href = '{{ route("admin.inventory.transactions") }}?' + params.toString();
}
</script>
@endpush