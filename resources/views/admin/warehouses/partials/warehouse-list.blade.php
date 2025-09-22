@forelse($warehouses as $warehouse)
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <div class="avatar-title bg-light text-primary rounded-3 fs-18">
                                <i class="ri-store-2-line"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-1">{{ $warehouse->name }}</h5>
                        <p class="text-muted mb-0">{{ $warehouse->code }}</p>
                    </div>
                </div>

                {{-- Phần thống kê --}}
                <div class="mt-3 pt-2 border-top">
                    <div class="row g-3">
                        <div class="col-6">
                            <div>
                                <p class="text-muted mb-1 fs-12">Tổng sản phẩm</p>
                                <h5 class="mb-0">{{ number_format($warehouse->total_products ?? 0) }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div>
                                <p class="text-muted mb-1 fs-12">Giá trị kho</p>
                                <h5 class="mb-0">{{ number_format($warehouse->total_value ?? 0) }}đ</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2 border-top">
                    <p class="mb-2"><i class="ri-map-pin-line align-middle text-muted me-1"></i>
                        {{ $warehouse->district }}, {{ $warehouse->city }}
                    </p>
                    <p class="mb-2"><i class="ri-user-3-line align-middle text-muted me-1"></i>
                        {{ $warehouse->manager_name }}
                    </p>
                    <p class="mb-0"><i class="ri-phone-line align-middle text-muted me-1"></i>
                        {{ $warehouse->phone }}
                    </p>
                </div>

                <div class="mt-3 hstack gap-2">
                    {{-- Toggle trạng thái hoạt động --}}
                    <div class="form-check form-switch">
                        <input class="form-check-input toggle-active" type="checkbox"
                            id="toggleActive{{ $warehouse->id }}" data-id="{{ $warehouse->id }}"
                            {{ $warehouse->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="toggleActive{{ $warehouse->id }}">
                            {{ $warehouse->is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}
                        </label>
                    </div>

                    {{-- Badge theo type --}}
                    @php
                        $typeColors = [
                            'Main' => 'danger', // đỏ
                            'Branch' => 'primary', // xanh dương
                            'Temporary' => 'secondary', // xám
                            'Distribution' => 'success', // xanh lá
                        ];

                        $typeLabels = [
                            'Main' => 'Kho chính',
                            'Branch' => 'Kho chi nhánh',
                            'Temporary' => 'Kho tạm',
                            'Distribution' => 'Kho phân phối',
                        ];
                    @endphp

                    <span class="badge bg-{{ $typeColors[$warehouse->type] ?? 'dark' }}">
                        {{ $typeLabels[$warehouse->type] ?? $warehouse->type }}
                    </span>
                </div>


                <div class="mt-3 hstack gap-2">
                    <button class="btn btn-sm btn-light w-100" onclick="viewWarehouse({{ $warehouse->id }})">
                        <i class="ri-eye-line align-bottom me-1"></i> Xem
                    </button>
                    <button class="btn btn-sm btn-soft-info w-100" onclick="editWarehouse({{ $warehouse->id }})">
                        <i class="ri-pencil-line align-bottom me-1"></i> Sửa
                    </button>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="text-center py-4">
            <p class="text-muted">Không có kho hàng nào.</p>
        </div>
    </div>
@endforelse
