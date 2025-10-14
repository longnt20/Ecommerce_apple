@extends('admin.layouts.app')
@section('title', 'Thêm mới danh mục')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí danh mục</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí danh mục</a></li>
                        <li class="breadcrumb-item">Danh sách danh mục</li>
                        <li class="breadcrumb-item">Thêm mới danh mục</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Thêm mới danh mục</h4>
                </div><!-- end card header -->

                <div class="card-body">
                    <form method="POST" action="{{route('admin.categories.store')}}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="firstNameinput" class="form-label">Tên danh mục</label>
                                    <input type="text" name="name" class="form-control" placeholder="Vui lòng nhập tên danh mục">
                                </div>
                            </div><!--end col-->
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="lastNameinput" class="form-label">Mô tả</label>
                                    <input type="text" name="description" class="form-control" placeholder="Vui lòng nhập mô tả">
                                </div>
                            </div><!--end col-->
                            <div class="col-4">
                                <div class="mb-3">
                                    <label for="phonenumberInput" class="form-label">Ảnh</label>
                                    <input type="file" name="image" class="form-control" placeholder="Chọn ảnh"
                                        >
                                </div>
                            </div><!--end col-->
                            {{-- Danh mục gốc --}}
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Danh mục gốc</label>
                                    <select name="parent_id" class="form-control">
                                        <option value="">-- Không có (danh mục cha) --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Trạng thái --}}
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select name="status" class="form-control">
                                        <option value="1">Kích hoạt</option>
                                        <option value="0">Ẩn</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div><!--end col-->
                        </div><!--end row-->
                    </form>
                </div><!-- end card -->
            </div>
            <!-- end col -->
        </div>
        <!-- end col -->
    </div>
@endsection
