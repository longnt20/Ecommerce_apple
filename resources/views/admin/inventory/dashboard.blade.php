{{-- resources/views/admin/inventory/dashboard.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Quản lý kho hàng')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="ri-store-3-line me-2"></i>Tổng quan kho hàng
            </h4>

            <div class="page-title-right">
                <div class="btn-group">
                    <a href="{{ route('admin.inventory.import') }}" class="btn btn-success">
                        <i class="ri-add-line me-1"></i>Nhập kho
                    </a>
                    <a href="{{ route('admin.inventory.transfer') }}" class="btn btn-info">
                        <i class="ri-arrow-left-right-line me-1"></i>Chuyển kho
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Tổng giá trị kho</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="ri-money-dollar-circle-line text-success fs-1"></i>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                            {{ number_format($totalValue) }} ₫
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Cảnh báo tồn kho thấp</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="ri-alert-line text-warning fs-1"></i>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $lowStockCount }}</h4>
                        <a href="{{ route('admin.inventory.index', ['status' => 'low_stock']) }}" class="text-decoration-underline">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Hết hàng</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="ri-error-warning-line text-danger fs-1"></i>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $outOfStockCount }}</h4>
                        <a href="{{ route('admin.inventory.index', ['status' => 'out_of_stock']) }}" class="text-decoration-underline">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Số kho hoạt động</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="ri-home-4-line text-info fs-1"></i>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $warehouses->count() }}</h4>
                        <a href="{{ route('admin.warehouses.index') }}" class="text-decoration-underline">
                            Quản lý kho
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Warehouse Overview -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tồn kho theo chi nhánh</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kho</th>
                                <th>Số SKU</th>
                                <th>Tổng SL</th>
                                <th>Giá trị</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouses as $warehouse)
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-0">{{ $warehouse->name }}</h6>
                                        <small class="text-muted">{{ $warehouse->code }}</small>
                                    </div>
                                </td>
                                <td>{{ $warehouse->inventory_count }}</td>
                                <td>{{ number_format($warehouse->inventory->sum('quantity')) }}</td>
                                <td>{{ number_format($warehouse->getTotalValue()) }} ₫</td>
                                <td>
                                    @if($warehouse->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-secondary">Tạm ngưng</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Lịch sử giao dịch gần đây</h5>
                <a href="{{ route('admin.inventory.transactions') }}" class="btn btn-sm btn-soft-primary">
                    Xem tất cả
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mã GD</th>
                                <th>Sản phẩm</th>
                                <th>Loại</th>
                                <th>SL</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $transaction)
                            <tr>
                                <td>
                                    <code>{{ $transaction->transaction_code }}</code>
                                </td>
                                <td>
                                    <small>{{ $transaction->productVariant->product->name }}</small>
                                </td>
                                <td>
                                    @php
                                    $typeColors = [
                                        'import' => 'success',
                                        'export' => 'danger',
                                        'transfer' => 'info',
                                        'adjust' => 'warning',
                                        'return' => 'secondary'
                                    ];
                                    $typeLabels = [
                                        'import' => 'Nhập',
                                        'export' => 'Xuất',
                                        'transfer' => 'Chuyển',
                                        'adjust' => 'Điều chỉnh',
                                        'return' => 'Trả hàng'
                                    ];
                                    @endphp
                                    <span class="badge bg-{{ $typeColors[$transaction->type] ?? 'primary' }}">
                                        {{ $typeLabels[$transaction->type] ?? $transaction->type }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->quantity > 0)
                                        <span class="text-success">+{{ $transaction->quantity }}</span>
                                    @else
                                        <span class="text-danger">{{ $transaction->quantity }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $transaction->created_at->diffForHumans() }}</small>
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

<!-- Low Stock Alert -->
@if($lowStockCount > 0)
<div class="row">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0">
                    <i class="ri-alert-line me-2"></i>Sản phẩm cần nhập thêm hàng
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Sản phẩm</th>
                                <th>Kho</th>
                                <th>Tồn hiện tại</th>
                                <th>Tồn tối thiểu</th>
                                <th>Cần nhập thêm</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                            <tr>
                                <td><code>{{ $item->productVariant->sku }}</code></td>
                                <td>
                                    {{ $item->productVariant->product->name }}
                                    <small class="d-block text-muted">
                                        {{ $item->productVariant->color }} - {{ $item->productVariant->storage }}
                                    </small>
                                </td>
                                <td>{{ $item->warehouse->name }}</td>
                                <td>
                                    <span class="badge bg-danger">{{ $item->quantity }}</span>
                                </td>
                                <td>{{ $item->min_stock_level }}</td>
                                <td>
                                    <strong>{{ $item->reorder_quantity ?: ($item->min_stock_level - $item->quantity + 10) }}</strong>
                                </td>
                                <td>
                                    <a href="{{ route('admin.inventory.import', ['product_variant_id' => $item->product_variant_id]) }}" 
                                       class="btn btn-sm btn-success">
                                        Nhập hàng
                                    </a>
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
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Add charts if needed
</script>
@endpush