@extends('admin.layouts.app')
@section('title')
    Chi tiết biến thể sản phẩm
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí biến thể sản phẩm</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí biến thể sản phẩm</a></li>
                        <li class="breadcrumb-item">Danh sách biến thể sản phẩm</li>
                        <li class="breadcrumb-item">Chi tiết biến thể sản phẩm</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Chi tiết biến thể sản phẩm : {{ $productVariant->product->name }}</h4>
                </div><!-- end card header -->

                <div class="card-body">
                    <div class="container mt-4">

                        <!-- Thông tin sản phẩm -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                Thông tin biến thể sản phẩm
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Tên sản phẩm:</div>
                                    <div class="col-md-9">{{ $productVariant->product->name }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Mã sản phẩm:</div>
                                    <div class="col-md-9">{{ $productVariant->sku }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Giá bán:</div>
                                    <div class="col-md-9">
                                        {{ number_format($productVariant->price, 0, ',', '.') }} đ
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Giá khuyến mãi:</div>
                                    <div class="col-md-9">
                                        {{ number_format($productVariant->cost_price, 0, ',', '.') }} đ
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Ảnh:</div>
                                    <div class="col-md-9">
                                        <img src="{{ asset('storage/' . $productVariant->thumbnail) }}" alt="Thumbnail"
                                            class="img-thumbnail" style="max-width:150px;">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Barcode:</div>
                                    <div class="col-md-9">
                                        <img src="data:image/png;base64,{!! DNS1D::getBarcodePNG($productVariant->barcode, 'C128') !!}" alt="barcode" />
                                        </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Màu:</div>
                                    <div class="col-md-9">{{ $productVariant->color }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Dung lượng:</div>
                                    <div class="col-md-9">{{ $productVariant->storage }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Ngày tạo:</div>
                                    <div class="col-md-9">{{ $productVariant->product->created_at }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Cập nhật:</div>
                                    <div class="col-md-9">{{ $productVariant->product->updated_at }}</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <a href="{{ route('admin.product_variants.index') }}" class="btn btn-secondary mt-3">Quay lại</a>
                </div><!-- end card-body -->
            </div><!-- end card -->
        </div><!-- end col -->
    </div><!-- end row -->
@endsection
