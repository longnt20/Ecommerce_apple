@extends('admin.layouts.app')
@push('page-css')
    <!-- plugin css -->
    <link href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush
@php
    $title = 'Cập nhật banner';
@endphp
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí banner</h4>

                <div class="page-title-right pe-3">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                        <li class="breadcrumb-item active"><a href="{{route('admin.banners.index')}}">Danh sách
                                banner</a></li>
                        <li class="breadcrumb-item active"><a href="{{route('admin.banners.edit',$banner->id)}}">Cập
                                nhật banner</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Cập nhật banner</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <form action="{{route('admin.banners.update',$banner->id)}}" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label class="form-label">Tiêu đề</label>
                                    <input type="text" name="title" class="form-control"
                                           value="{{$banner->title}}">
                                    @error('title')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Chuyển hướng</label>
                                    <input type="text" name="redirect_url" class="form-control"
                                           value="{{ $banner->redirect_url }}">
                                    @error('redirect_url')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hình ảnh</label>
                                    <input type="file" name="image" class="form-control">
                                    <img class="mt-2" src="{{Storage::url($banner->image)}}" alt="" srcset="" width="200px">
                                    @error('image')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nội dung</label>
                                    <input type="text" name="content" class="form-control"
                                           value="{{ $banner->content }}">
                                    @error('content')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Thứ tự</label>
                                    <input type="int" name="order" class="form-control"
                                           value="{{ $banner->order }}">
                                    @error('order')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" value="{{ old('status') }}">
                                        <option value="1" <?php echo $banner->status == 1 ? 'selected' : '' ?>>
                                            Active
                                        </option>
                                        <option value="0" <?php echo $banner->status == 0 ? 'selected' : '' ?>>
                                            InActive
                                        </option>
                                    </select>
                                    @error('status')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Cập nhật
                                    </button>
                                    <button type="reset" class="btn btn-soft-primary waves-effect waves-light">
                                        Nhập
                                        lại
                                    </button>
                                    <a class="btn btn-dark" href="{{ route('admin.banners.index') }}">Danh
                                        sách</a>
                                </div>
                            </form>
                        </div>
                    </div><!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end col -->
        </div>


    </div>
@endsection
