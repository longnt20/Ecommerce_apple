@extends('admin.layouts.app')
@section('title', 'Danh sách thuộc tính')
@section('content')
    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Quản lý thuộc tính sản phẩm</h4>
                    <a href="{{ route('admin.product-attributes.create') }}" class="btn btn-primary float-end">
                        <i class="ri-add-line"></i> Thêm mới
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Form lọc -->
                    <form method="GET" action="{{ route('admin.product-attributes.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Tất cả loại --</option>
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Tìm kiếm..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                                <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>

                    <!-- Bảng dữ liệu -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Loại</th>
                                    <th>Giá trị</th>
                                    <th>Nhãn hiển thị</th>
                                    <th>Thứ tự</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attributes as $attribute)
                                    <tr>
                                        <td>{{ $attribute->id }}</td>
                                        <td>{{ $types[$attribute->type] ?? $attribute->type }}</td>
                                        <td>{{ $attribute->value }}</td>
                                        <td>{{ $attribute->label }}</td>
                                        <td>{{ $attribute->sort_order }}</td>
                                        <td>
                                            <span class="badge bg-{{ $attribute->is_active ? 'success' : 'danger' }}">
                                                {{ $attribute->is_active ? 'Hoạt động' : 'Tạm ẩn' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.product-attributes.edit', $attribute) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form action="{{ route('admin.product-attributes.destroy', $attribute) }}" 
                                                  method="POST" class="d-inline-block"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Phân trang -->
                    {{ $attributes->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection