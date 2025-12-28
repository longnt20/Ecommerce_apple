@extends('admin.layouts.app')
@section('title', 'Danh sách khung')
@push('page-css')
<style>
.frame-item {
    background: #fff;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,.06);
}

.frame-preview-mini {
    position: relative;
    height: 180px;
    background: #f3f3f3;
    border-radius: 10px;
    overflow: hidden;
}

.frame-preview-mini div {
    position: absolute;
    background-repeat: no-repeat;
    background-size: contain;
    background-position: center;
}
       .bg-top {
            top: 10px;
            left: 12px;
            width: 95%;
            height: 55px;
            z-index: 1;
        }

        .bg-bottom {
            top: 50px;
            width: 100%;
            height: 130px;
            z-index: 2;
        }

        .ribbon {
            width: 100%;
            height: 15%;
            top: 15px;
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
            top: 40px;
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
            top: 40px;
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
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Danh sách khung</h4>
                    <a href="{{route('admin.frames.create')}}" class="btn btn-success">Thêm mới khung</a>
                </div><!-- end card header -->
<div class="row">
    @foreach($frames as $frame)
        <div class="col-md-4 mb-4">
            <div class="frame-item">
                <div class="frame-preview-mini mb-2">
                    @if($frame->top_background)
                        <div class="bg-top" style="background-image:url({{ asset(Storage::url($frame->top_background)) }})"></div>
                    @endif

                    @if($frame->bottom_background)
                        <div class="bg-bottom" style="background-image:url({{ asset(Storage::url($frame->bottom_background)) }})"></div>
                    @endif

                    @if($frame->ribbon_image)
                        <div class="ribbon" style="background-image:url({{ asset(Storage::url($frame->ribbon_image)) }})"></div>
                    @endif

                    @if($frame->left_decor_image)
                        <div class="decor-left" style="background-image:url({{ asset(Storage::url($frame->left_decor_image)) }})"></div>
                    @endif

                    @if($frame->right_decor_image)
                        <div class="decor-right" style="background-image:url({{ asset(Storage::url($frame->right_decor_image)) }})"></div>
                    @endif
                </div>

                <h6 class="mb-1">{{ $frame->name }}</h6>
                <small class="text-muted">
                    {{ $frame->created_at->format('d/m/Y') }}
                </small>

                <div class="mt-2 d-flex gap-2">
                    <a href="{{ route('admin.frames.edit', $frame) }}" class="btn btn-sm btn-warning">
                        Sửa
                    </a>

                    <form method="POST" action="{{ route('admin.frames.destroy', $frame) }}">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Xóa khung này?')" class="btn btn-sm btn-danger">
                            Xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-3">
    {{ $frames->links() }}
</div>
            </div>
            <!-- end col -->
        </div>
        <!-- end col -->
    </div>
@endsection
