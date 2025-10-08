@extends('admin.layouts.app')
@section('title', 'Thêm mới người dùng')
@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Thêm mới người dùng</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a
                                    href="{{ route('admin.users.index') }}">Danh
                                    sách người
                                    dùng</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('admin.users.create') }}">Thêm mới
                                    người
                                    dùng</a></li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Thêm mới người dùng</h4>
            </div><!-- end card header -->
            <div class="card-body">
                <div class="live-preview">
                    <form action="{{ route('admin.users.store') }}" method="post" enctype="multipart/form-data"
                          class="row g-3">
                        @csrf
                        <div class="col-md-12">
                            <label for="fullname" class="form-label">Họ Và Tên</label>
                            <input type="text" class="form-control mb-2" name="name" id="fullname"
                                   placeholder="Nhập họ và tên của bạn" value="{{ old('name') }}">
                            @error('name')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="inputEmail4" class="form-label">Email</label>
                            <input type="email" class="form-control mb-2" name="email" id="inputEmail4"
                                   placeholder="Nhập email" value="{{ old('email') }}">
                            @error('email')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="passwordInput" class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5 mb-2" name="password" id="passwordInput"
                                       placeholder="Nhập password">
                                <i class="ri-eye-off-line position-absolute end-0 top-50 translate-middle-y pe-3"
                                   id="togglePassword" style="cursor: pointer; color: blue;"></i>
                            </div>
                            @error('password')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 position-relative">
                            <label for="repasswordInput" class="form-label">Repassword</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5 mb-2" name="repassword"
                                       id="repasswordInput"
                                       placeholder="Nhập lại password">
                                <i class="ri-eye-off-line position-absolute end-0 top-50 translate-middle-y pe-3"
                                   id="toggleRepassword" style="cursor: pointer; color: blue;"></i>
                            </div>
                            @error('repassword')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" id="imageInput" accept="image/*" class="form-control">
                            <img class="mt-2" id="imagePreview"
                                 style="display: none; max-width: 100%; max-height: 300px;">
                            @error('avatar')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="">Trạng thái</label>
                            <select name="status" id="" class="form-select mb-2">
                                <option value="">Chọn trạng thái</option>
                                <option @selected(old('status') == 'active') value="active">Active</option>
                                <option @selected(old('status') == 'inactive') value="inactive">Inactive</option>
                                <option @selected(old('status') == 'blocked') value="blocked">Blocked</option>
                            </select>
                            @error('status')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="">Vai trò</label>
                            <select name="role" id="" class="form-select mb-2">
                                <option value="">Chọn vai trò</option>
                                    <option {{ old('role') == 'admin' ? 'selected' : '' }} value="admin">
                                        admin</option>
                                         <option {{ old('role') == 'user' ? 'selected' : '' }} value="user">
                                        user</option>
                            </select>
                            @error('role')
                            <span class="text-danger mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Thêm mới</button>
                            <button type="reset" class="btn btn-secondary ">Nhập lại</button>
                            <a class="btn btn-dark"
                               href="{{ route('admin.users.index') }}">Quay
                                lại</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('imageInput').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = () => {
                    const preview = document.getElementById('imagePreview');
                    preview.src = reader.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('passwordInput');
            const icon = this;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            }
        });

        // Toggle Repassword Visibility
        document.getElementById('toggleRepassword').addEventListener('click', function () {
            const repasswordInput = document.getElementById('repasswordInput');
            const icon = this;

            if (repasswordInput.type === 'password') {
                repasswordInput.type = 'text';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            } else {
                repasswordInput.type = 'password';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            }
        });
    </script>
@endpush
