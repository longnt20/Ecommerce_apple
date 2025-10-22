@extends('admin.layouts.app')
@push('page-css')
    <!-- plugin css -->
    <link href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
@endpush
@php
    $title = 'Chi tiết banner';
@endphp
@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Chi tiết Banner</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.banners.index') }}">Danh sách Banner</a></li>
                    <li class="breadcrumb-item active">Chi tiết Banner</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">

    @if (session('success') === true)
        <div class="alert alert-success">Thao tác thành công</div>
    @elseif (session('success') === false)
        <div class="alert alert-danger"><strong>Thao tác không thành công</strong></div>
    @endif

    <div class="row">
        <!-- Thông tin chi tiết -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">Thông tin Banner</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <span><strong>Tiêu đề:</strong></span>
                            <span class="text-end">{{ $banner->title }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <span><strong>Đường dẫn:</strong></span>
                            <span class="text-end">
                                <a href="{{ $banner->redirect_url }}" target="_blank">
                                    {{ $banner->redirect_url }}
                                </a>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <span><strong>Nội dung:</strong></span>
                            <span class="text-end">{{ $banner->content }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <span><strong>Thứ tự hiển thị:</strong></span>
                            <span class="text-end">{{ $banner->order }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <span><strong>Trạng thái:</strong></span>
                            <span class="text-end">
                                @if($banner->status)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Không hoạt động</span>
                                @endif
                            </span>
                        </li>
                    </ul>

                    <div class="mt-4">
                        <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="la la-arrow-left me-1"></i> Trở về danh sách
                        </a>
                        <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-warning">
                            <i class="la la-edit me-1"></i> Chỉnh sửa
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ảnh Banner -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">Ảnh Banner</h5>
                </div>
                <div class="card-body text-center">
                    @if ($banner->image)
                        <img src="{{Storage::url($banner->image)}}" alt="Banner" class="img-fluid">

                    @else
                        <div class="text-muted fst-italic">Chưa có ảnh</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



