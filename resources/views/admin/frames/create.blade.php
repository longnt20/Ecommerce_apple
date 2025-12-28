@extends('admin.layouts.app')
@section('title', 'Thêm mới khung')
@push('page-css')
    <style>
        .frame-preview {
            position: relative;
            width: 100%;
            height: 280px;
            background: #f3f3f3;
            border-radius: 12px;
            overflow: hidden;
        }

        .frame-preview div {
            position: absolute;
            max-width: 100%;
            object-fit: contain;
        }

        .bg-top {
            top: 20px;
            left: 12px;
            width: 95%;
            height: 20%;
            background-repeat: no-repeat;
            background-position: top;
            background-size: contain;
            z-index: 1;
        }

        .bg-bottom {
            top: 20px;
            width: 100%;
            height: 72%;
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            z-index: 2;
        }

        .ribbon {
            top: 10px;
            width: 100%;
            height: 15%;
            left: 25%;
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            z-index: 5;
        }

        .decor-left {
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            left: 20px;
            top: 0;
            width: 40px;
            z-index: 4;
            height: 30%;
        }

        .decor-right {
            transform: translateX(-50%);
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: contain;
            right: -20px;
            top: 0;
            width: 40px;
            z-index: 4;
            height: 30%;
        }
    </style>
@endpush
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Quản lí khung</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Quản lí khung</a></li>
                        <li class="breadcrumb-item">Danh sách khung</li>
                        <li class="breadcrumb-item">Thêm mới khung</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Thêm mới khung</h4>
                </div><!-- end card header -->

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.frames.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            {{-- FORM INPUT --}}
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label>Tên khung</label>
                                        <input type="text" name="name" class="form-control">
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label>Ảnh nền trên</label>
                                        <input type="file" name="top_background" class="form-control image-input"
                                            data-preview="preview-top">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label>Ảnh nền dưới</label>
                                        <input type="file" name="bottom_background" class="form-control image-input"
                                            data-preview="preview-bottom">
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label>Ảnh nền tiêu đề</label>
                                        <input type="file" name="ribbon_image" class="form-control image-input"
                                            data-preview="preview-ribbon">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label>Decor Trái</label>
                                        <input type="file" name="left_decor_image" class="form-control image-input"
                                            data-preview="preview-left">
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label>Decor Phải</label>
                                        <input type="file" name="right_decor_image" class="form-control image-input"
                                            data-preview="preview-right">
                                    </div>
                                </div>

                                <button class="btn btn-primary">Lưu Khung</button>
                            </div>

                            {{-- PREVIEW --}}
                            <div class="col-md-5">
                                <label>Preview Frame</label>

                                <div class="frame-preview">
                                    <div id="preview-top" class="bg-top"></div>
                                    <div id="preview-bottom" class="bg-bottom"></div>
                                    <div id="preview-ribbon" class="ribbon"></div>
                                    <div id="preview-left" class="decor-left"></div>
                                    <div id="preview-right" class="decor-right"></div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div><!-- end card -->
            </div>
            <!-- end col -->
        </div>
        <!-- end col -->
    </div>
@endsection
@push('scripts')
    <script>
document.querySelectorAll('.image-input').forEach(input => {
    input.addEventListener('change', function () {
        const previewId = this.dataset.preview;
        const preview = document.getElementById(previewId);

        if (!this.files || !this.files[0]) return;

        const reader = new FileReader();

        reader.onload = function (e) {
            preview.style.backgroundImage = `url(${e.target.result})`;
        };

        reader.readAsDataURL(this.files[0]);
    });
});
</script>

@endpush
