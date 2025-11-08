@extends('admin.layouts.app')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('vendor/laraberg/css/laraberg.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Quản lý bài viết</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Chi tiết bài viết</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">
                            Thông tin bài viết <span class="text-danger">{{ $post->title }}</span>
                        </h4>
                        <div class="form-check form-switch">
                            <label class="form-check-label" for="isHotSwitch">
                                {{ $post->is_hot ? 'Bài viết hot 🔥' : 'Không hot 🔥' }}
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Hình đại diện:</label>
                            <img class="img-thumbnail" src="{{ $post->thumbnail }}" alt="Hình đại diện">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả bài viết:</label>
                            <textarea disabled class="form-control" cols="30" rows="10">{{ trim($post->description ?? '') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung:</label>
                            <textarea disabled class="form-control" cols="30" rows="15">{{ trim($post->content ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề:</label>
                            <p class="text-muted">{{ $post->title }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Người tạo bài:</label>
                            <p class="text-muted">{{ $post->user->name }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái:</label>
                            <p class="text-muted">{{ ucfirst($post->status) }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày xuất bản:</label>
                            <p class="text-muted">
                                {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('Y/m/d H:i') : 'Chưa xuất bản' }}
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Danh mục:</label>
                            <select class="select2-categories form-control" multiple="multiple" disabled>
                                <option selected>{{ $post->category->name ?? '' }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags:</label>
                            <select class="select2-tags form-control" multiple="multiple" disabled>
                                @foreach ($post->tags as $tag)
                                    <option selected>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Đường dẫn thân thiện:</label>
                            <p class="text-muted">{{ $post->slug }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">View:</label>
                            <p class="text-muted">{{ $post->views }} lượt xem</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày tạo bài:</label>
                            <p class="text-muted">{{ $post->created_at ? $post->created_at->format('d/m/Y H:i') : '' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày chỉnh sửa:</label>
                            <p class="text-muted">{{ $post->updated_at ? $post->updated_at->format('d/m/Y H:i') : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-warning">Quay lại danh sách</a>
                    <a href="{{ route('admin.posts.edit', $post->id) }}" class="btn btn-primary">Chỉnh sửa bài viết</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/react@17.0.2/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@17.0.2/umd/react-dom.production.min.js"></script>

    <script src="{{ asset('vendor/laraberg/js/laraberg.js') }}"></script>
    <script>
        $(document).ready(function() {
            Laraberg.init('laraberg');

            $('.select2-categories').select2({
                placeholder: 'Không có'
            });

            $('.select2-tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: 'Không có'
            });
        });
    </script>
    <script>
        @once
        let myEditor;
        ClassicEditor.create(document.querySelector('#description'))
            .then(editor => {
                myEditor = editor;
            });

        document.querySelector('#postForm').addEventListener('submit', function() {
            document.querySelector('#description').value = myEditor.getData();
        });
        @endonce
    </script>
@endpush
