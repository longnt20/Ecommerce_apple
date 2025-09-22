@extends('admin.layouts.app')
@section('title', 'Danh sách thuộc tính')
@section('content')
    <div class="container-fluid">
        <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí thuộc tính sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí biến thể sản phẩm</a></li>
                        <li class="breadcrumb-item">Danh sách thuộc tính sản phẩm</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
        <!-- Bảng thuộc tính màu sắc -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Quản lý thuộc tính màu sắc</h4>
                    </div>
                    
                    <div class="card-body">
                        <!-- Form tìm kiếm cho màu -->
                        <form method="GET" action="{{ route('admin.product-attributes.index') }}" class="mb-3">
                            <input type="hidden" name="type" value="color">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="search_color" class="form-control" 
                                           placeholder="Tìm kiếm màu sắc..." value="{{ request('search_color') }}">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                                </div>
                                <div class="col-md-4">
                                    <a href="{{ route('admin.product-attributes.create', ['type' => 'color']) }}" class="btn btn-primary float-end">
                            <i class="ri-add-line"></i> Thêm màu mới
                        </a>
                                </div>
                            </div>
                        </form>

                        <!-- Bảng màu sắc -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Màu sắc</th>
                                        <th>Mã màu</th>
                                        <th>Nhãn hiển thị</th>
                                        <th>Thứ tự</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($colorAttributes as $attribute)
                                        <tr>
                                            <td>{{ $attribute->id }}</td>
                                            <td>
                                                @if($attribute->value)
                                                        <span style="background-color: {{ $attribute->hex_code }};
                                                                    display: inline-block;
                                                                    width: 20px;
                                                                    height: 20px;
                                                                    border-radius: 50%;
                                                                    border: 1px solid #ccc;">
                                                        </span>
                                                    @endif
                                            </td>
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
                                            <td colspan="7" class="text-center">Không có dữ liệu màu sắc</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang màu sắc -->
                        {{ $colorAttributes->withQueryString()->links('pagination::bootstrap-4', ['pageName' => 'color_page']) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng thuộc tính dung lượng -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Quản lý thuộc tính dung lượng</h4>
                    </div>
                    
                    <div class="card-body">
                        <!-- Form tìm kiếm cho dung lượng -->
                        <form method="GET" action="{{ route('admin.product-attributes.index') }}" class="mb-3">
                            <input type="hidden" name="type" value="storage">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="search_storage" class="form-control" 
                                           placeholder="Tìm kiếm dung lượng..." value="{{ request('search_storage') }}">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                                </div>
                                <div class="col-md-4">
                                     <a href="{{ route('admin.product-attributes.create', ['type' => 'storage']) }}" class="btn btn-primary float-end">
                            <i class="ri-add-line"></i> Thêm dung lượng mới
                        </a>
                                </div>
                            </div>
                        </form>

                        <!-- Bảng dung lượng -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Dung lượng</th>
                                        <th>Nhãn hiển thị</th>
                                        <th>Thứ tự</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($storageAttributes as $attribute)
                                        <tr>
                                            <td>{{ $attribute->id }}</td>
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
                                            <td colspan="6" class="text-center">Không có dữ liệu dung lượng</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang dung lượng -->
                        {{ $storageAttributes->withQueryString()->links('pagination::bootstrap-4', ['pageName' => 'storage_page']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection