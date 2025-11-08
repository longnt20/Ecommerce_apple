@extends('admin.layouts.app')
@section('title')
    Chi tiết sản phẩm
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí sản phẩm</a></li>
                        <li class="breadcrumb-item">Danh sách sản phẩm</li>
                        <li class="breadcrumb-item">Chi tiết sản phẩm</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Chi tiết sản phẩm : {{ $product->name }}</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger mt-2">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div><!-- end card header -->

                <div class="card-body">
                    <div class="container mt-4">

                        <!-- Thông tin sản phẩm -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                Thông tin sản phẩm
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Tên sản phẩm:</div>
                                    <div class="col-md-9">{{ $product->name }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Slug:</div>
                                    <div class="col-md-9">{{ $product->slug }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Danh mục:</div>
                                    <div class="col-md-9">
                                        {{ $product->category->name ?? 'Không có' }}
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Ảnh đại diện:</div>
                                    <div class="col-md-9">
                                        <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="Thumbnail"
                                            class="img-thumbnail" style="max-width:150px;">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Thư viện ảnh:</div>
                                    <div class="col-md-9">
                                        @foreach ($product->gallery ?? [] as $img)
                                            <img src="{{ asset('storage/' . $img) }}" alt="Gallery"
                                                class="img-thumbnail me-2 mb-2" style="max-width:120px;">
                                        @endforeach
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Giá:</div>
                                    <div class="col-md-9">{{ number_format($product->default_price, 0, ',', '.') }} đ</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Trạng thái hiển thị:</div>
                                    <div class="col-md-9">
                                        @if ($product->visibility == 'public')
                                            <span class="badge bg-secondary px-2 py-2">Công khai</span>
                                        @else
                                            <span class="badge bg-danger px-2 py-2">Ẩn</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Trạng thái:</div>
                                    <div class="col-md-9">
                                        @if ($product->status == 'published')
                                            <span class="badge bg-secondary px-2 py-2">Đã phát hành</span>
                                        @else
                                            <span class="badge bg-light px-2 py-2">Bản nháp</span>
                                        @endif

                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Ngày tạo:</div>
                                    <div class="col-md-9">{{ $product->created_at }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Cập nhật:</div>
                                    <div class="col-md-9">{{ $product->updated_at }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông số kỹ thuật -->
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                Thông số kỹ thuật
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tên thông số</th>
                                            <th>Giá trị</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($product->specs as $spec)
                                            <tr>
                                                <td>{{ $spec->spec_name }}</td>
                                                <td>{{ $spec->spec_value }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">Chưa có thông số kỹ thuật</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                Biến thể
                            </div>
                            <div class="card-body">
                                @if ($product->variants->isEmpty())
                                    <p><em>Không có biến thể nào.</em></p>
                                @else
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Sku</th>
                                                <th>Barcode</th>
                                                <th>Ảnh</th>
                                                <th>Màu sắc</th>
                                                <th>Dung lượng</th>
                                                <th>Giá bán</th>
                                                <th>Giá khuyến mãi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($product->variants as $index => $variant)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $variant->sku }}</td>
                                                    <td>
                                                        <img src="data:image/png;base64,{!! DNS1D::getBarcodePNG($variant->barcode, 'C128') !!}" alt="barcode" />
                                                    </td>
                                                    <td>
                                                        <img src="{{ Storage::url($variant->thumbnail) }}" alt="" width="100px">
                                                    </td>
                                                    <td>{{ $variant->color ?? '-' }}</td>
                                                    <td>{{ $variant->storage ?? '-' }}</td>
                                                    <td>{{ number_format($variant->price) }}đ</td>
                                                    <td>{{ number_format($variant->cost_price) }}đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                        <!-- Mô tả chi tiết -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-primary text-white fw-bold">
                                Mô tả sản phẩm
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6 class="fw-semibold text-muted">Mô tả ngắn</h6>
                                    <p class="p-3 border rounded bg-light">
                                        {{ $product->short_description }}
                                    </p>
                                </div>

                                <div>
                                    <h6 class="fw-semibold text-muted">Mô tả chi tiết</h6>
                                    <div class="p-3 border rounded bg-light">
                                        {!! $product->description !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary mt-3">Quay lại</a>
                </div><!-- end card-body -->
            </div><!-- end card -->
        </div><!-- end col -->
    </div><!-- end row -->
@endsection
