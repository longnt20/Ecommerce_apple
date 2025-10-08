@extends('admin.layouts.app')
@section('title','Chi tiết người dùng')
@push('page-css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .user-card {
            border-radius: 0.75rem;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: none;
            overflow: hidden;
        }

        .user-card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
            padding: 1.25rem 1.5rem;
        }

        .user-avatar-container {
            background-color: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 2rem 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .user-avatar-container:hover {
            transform: translateY(-5px);
        }

        .user-avatar {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .user-name {
            font-weight: 700;
            margin-top: 1.5rem;
            font-size: 1.25rem;
            color: #333;
        }

        .user-code {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .verification-badge {
            margin-top: 1.25rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .user-info-section {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            height: 100%;
        }

        .user-info-row {
            padding: 1rem 0;
            border-bottom: 1px solid #eaeaea;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
        }

        .user-info-row:last-child {
            border-bottom: none;
        }

        .user-info-row:hover {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding-left: 10px;
        }

        .user-info-label {
            font-weight: 600;
            color: #495057;
        }

        .user-info-value {
            color: #212529;
        }

        .user-info-icon {
            margin-right: 8px;
            color: #6c757d;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .status-badge i {
            margin-right: 0.35rem;
            font-size: 0.8rem;
        }

        .bio-content {
            line-height: 1.6;
            padding: 0.75rem;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 3px solid #6c757d;
        }

        .action-buttons {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            padding: 0.5rem 1.25rem;
            margin-right: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        .action-buttons .btn i {
            margin-right: 0.5rem;
        }

        .page-title-box {
            margin-bottom: 1.5rem;
        }

        .breadcrumb-item a {
            display: flex;
            align-items: center;
        }

        @media (max-width: 768px) {
            .user-avatar {
                width: 120px;
                height: 120px;
            }

            .user-avatar-container {
                margin-bottom: 1.5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Chi tiết người dùng</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.dashboard') }}">
                                    <i class="mdi mdi-home-outline me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a
                                    href="{{ route('admin.users.index') }}">
                                    Danh sách người dùng
                                </a>
                            </li>
                            <li class="breadcrumb-item active">Chi tiết người dùng</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card user-card">
                    <div class="card-header user-card-header">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="mdi mdi-account-circle me-1"></i>
                                Thông tin chi tiết người dùng:
                                <span class="text-primary">{{ $user->name }}</span>

                                @if ($user->status === 'active')
                                    <span class="status-badge bg-success text-white ms-2">
                                        <i class="mdi mdi-check-circle"></i>Hoạt động
                                    </span>
                                @elseif($user->status === 'inactive')
                                    <span class="status-badge bg-warning text-white ms-2">
                                        <i class="mdi mdi-clock-outline"></i>Chưa kích hoạt
                                    </span>
                                    <span class="status-badge bg-danger text-white ms-2">
                                        <i class="mdi mdi-block-helper"></i>Đã khóa
                                    </span>
                                @endif
                            </h5>

                            <div>
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-pencil me-1"></i>Chỉnh sửa
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row">
                            <!-- User Avatar Column -->
                            <div class="col-lg-3 col-md-4">
                                <div class="user-avatar-container">
                                    <img src="{{ $user->avatar ? Storage::url($user->avatar) : \App\Http\Controllers\Admin\UserController::URLIMAGEDEFAULT }}" alt="Avatar của {{ $user->name }}"
                                        class="img-fluid rounded-circle user-avatar">
                                    <h5 class="user-name">{{ $user->name }}</h5>
                                    <p class="user-code">{{ $user->code }}</p>

                                    <div
                                        class="verification-badge {{ $user->email_verified_at ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }}">
                                        <i
                                            class="mdi {{ $user->email_verified_at ? 'mdi-check-circle' : 'mdi-alert-circle' }} me-1"></i>
                                        {{ $user->email_verified_at ? 'Đã xác minh' : 'Email chưa xác minh' }}
                                    </div>

                                    <div class="mt-4">
                                        <a href="{{ route('admin.users.edit', $user->id) }}"
                                            class="btn btn-sm btn-outline-primary w-100 mb-2">
                                            <i class="mdi mdi-pencil me-1"></i>Chỉnh sửa thông tin
                                        </a>

                                        @if ($user->status !== 'active')
                                            <button class="btn btn-sm btn-outline-success w-100">
                                                <i class="mdi mdi-check-circle me-1"></i>Kích hoạt tài khoản
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning w-100">
                                                <i class="mdi mdi-lock me-1"></i>Tạm khóa tài khoản
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- User Info Column -->
                            <div class="col-lg-9 col-md-8">
                                <div class="user-info-section">
                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-identifier user-info-icon"></i>Mã người dùng:
                                        </div>
                                        <div class="col-md-9 user-info-value">{{ $user->code }}</div>
                                    </div>

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-account user-info-icon"></i>Họ và tên:
                                        </div>
                                        <div class="col-md-9 user-info-value">{{ $user->name }}</div>
                                    </div>

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-email user-info-icon"></i>Email:
                                        </div>
                                        <div class="col-md-9 user-info-value">{{ $user->email }}</div>
                                    </div>

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-phone user-info-icon"></i>Số điện thoại:
                                        </div>
                                        <div class="col-md-9 user-info-value">
                                            @if (!empty($user->phone))
                                                {{ $user->phone }}
                                            @else
                                                <span class="text-muted fst-italic">Chưa có thông tin</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-map-marker user-info-icon"></i>Địa chỉ:
                                        </div>
                                        <div class="col-md-9 user-info-value">
                                            @if (!empty($user->profile->address))
                                                {{ $user->profile->address }}
                                            @else
                                                <span class="text-muted fst-italic">Chưa có thông tin</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-briefcase user-info-icon"></i>Kinh nghiệm:
                                        </div>
                                        <div class="col-md-9 user-info-value">
                                            @if (!empty($user->profile->experience))
                                                {{ $user->profile->experience }}
                                            @else
                                                <span class="text-muted fst-italic">Chưa có thông tin</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-text-box user-info-icon"></i>Tiểu sử:
                                        </div>
                                        <div class="col-md-9 user-info-value">
                                            @if (!empty($user->profile->bio))
                                                <div>
                                                    @php
                                                        $socials = json_decode($user->profile->bio, true) ?? '';
                                                        $socials = is_array($socials) ? $socials : [];
                                                        $filledSocials = array_filter($socials);
                                                    @endphp
                                                    <div class="card-body">
                                                        @php
                                                            $icon = [
                                                                'facebook' => 'ri-facebook-fill',
                                                                'twitter' => 'ri-twitter-fill',
                                                                'instagram' => 'ri-instagram-fill',
                                                                'linkedin' => 'ri-linkedin-fill',
                                                                'github' => 'ri-github-fill',
                                                                'dribbble' => 'ri-dribbble-fill',
                                                                'youtube' => 'ri-youtube-fill',
                                                                'website' => 'ri-global-fill',
                                                            ];
                                                        @endphp

                                                        @if (!empty($filledSocials))
                                                            <div class="d-flex flex-wrap gap-3">
                                                                @foreach ($socials as $key => $url)
                                                                    @if (array_key_exists($key, $icon) && $url)
                                                                        <a href="{{ $url }}"
                                                                            class="btn btn-soft-primary btn-sm"
                                                                            target="_blank">
                                                                            <i class="{{ $icon[$key] }} me-1"></i>
                                                                            {{ ucfirst($key) }}
                                                                        </a>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="text-center py-3">
                                                                <div class="avatar-sm mx-auto mb-3">
                                                                    <span
                                                                        class="avatar-title rounded-circle bg-light text-body fs-20">
                                                                        <i class="ri-links-line"></i>
                                                                    </span>
                                                                </div>
                                                                <p class="text-muted mb-0">Người dùng chưa thêm liên kết
                                                                    mạng xã hội</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted fst-italic">Chưa có thông tin</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($roleUser == 'instructor')
                                        <div class="row user-info-row">
                                            <div class="col-md-3 user-info-label">
                                                <i class="mdi mdi-account-group user-info-icon"></i>Tổng số học viên:
                                            </div>
                                            <div class="col-md-9 user-info-value">
                                                @if ($totalStudents > 0)
                                                    {{ $totalStudents }}
                                                @else
                                                    <span class="text-muted fst-italic">Chưa có học viên</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row user-info-row">
                                            <div class="col-md-3 user-info-label">
                                                <i class="mdi mdi-cash-multiple user-info-icon"></i>Hoa hồng nhận được:
                                            </div>
                                            <div class="col-md-9 user-info-value">
                                                <span class="badge bg-primary">
                                                    {{ fmod($user->instructorCommissions->rate * 100, 1) == 0
                                                        ? number_format($user->instructorCommissions->rate * 100, 0)
                                                        : number_format($user->instructorCommissions->rate * 100, 2) }}%
                                                </span>
                                            </div>
                                        </div>

                                        <div class="row user-info-row">
                                            <div class="col-md-3 user-info-label">
                                                <i class="mdi mdi-bank user-info-icon"></i>Tổng doanh thu:
                                            </div>
                                            <div class="col-md-9 user-info-value">
                                                @if (!empty($totalRevenueInstructor->total_revenue))
                                                    <span class="badge bg-info">
                                                        {{ number_format($totalRevenueInstructor->total_revenue ?? 0) }}
                                                        VND
                                                    </span>
                                                @else
                                                    <span class="text-muted fst-italic">Chưa có doanh thu</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row user-info-row">
                                            <div class="col-md-3 user-info-label">
                                                <i class="mdi mdi-domain user-info-icon"></i>Hệ thống nhận được:
                                            </div>
                                            <div class="col-md-9 user-info-value">
                                                @if (!empty($totalRevenueInstructor->total_instructor_share))
                                                    <span class="badge bg-success">
                                                        {{ number_format($totalRevenueInstructor->total_instructor_share ?? 0) }}
                                                        VND
                                                    </span>
                                                @else
                                                    <span class="text-muted fst-italic">Giảng viên chưa có doanh thu</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if ($roleUser != 'employee')
                                        <div class="row user-info-row">
                                            <div class="col-md-3 user-info-label">
                                                <i class="mdi mdi-cash-refund user-info-icon"></i>Tổng tiền đã chi:
                                            </div>
                                            <div class="col-md-9 user-info-value">
                                                @if (!empty($totalSpent->totalSpent))
                                                    <span class="badge bg-danger">
                                                        {{ number_format($totalSpent->totalSpent ?? 0) }} VND
                                                    </span>
                                                @else
                                                    <span class="text-muted fst-italic">Chưa chi tiền</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif --}}

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-calendar user-info-icon"></i>Thời gian tạo:
                                        </div>
                                        <div class="col-md-9 user-info-value">
                                            {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y H:i:s') }}
                                        </div>
                                    </div>

                                    <div class="row user-info-row">
                                        <div class="col-md-3 user-info-label">
                                            <i class="mdi mdi-update user-info-icon"></i>Cập nhật lần cuối:
                                        </div>
                                        <div class="col-md-9 user-info-value">
                                            {{ \Carbon\Carbon::parse($user->updated_at)->format('d/m/Y H:i:s') }}
                                        </div>
                                    </div>
                                        <div class="mt-4">
                                            <h5 class="mb-3 fw-bold">
                                                📊 Thông tin hoạt động của <span
                                                    class="text-primary">{{ $user->name }}</span>
                                            </h5>

                                            {{-- <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-courses"
                                                        role="tab">
                                                        <i class="mdi mdi-book-open-page-variant me-1"></i> Khóa học
                                                        @if ($courses->count() > 0)
                                                            <span
                                                                class="badge bg-soft-primary text-primary">{{ $courses->total() }}</span>
                                                        @endif
                                                    </a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-memberships"
                                                        role="tab">
                                                        <i class="mdi mdi-certificate me-1"></i> Gói Membership
                                                        @if (isset($memberships) && $memberships->count() > 0)
                                                            <span
                                                                class="badge bg-soft-info text-info">{{ $memberships->total() }}</span>
                                                        @endif
                                                    </a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-purchases"
                                                        role="tab">
                                                        <i class="mdi mdi-cart me-1"></i> Lịch sử mua hàng
                                                        @if (isset($purchases) && $purchases->count() > 0)
                                                            <span
                                                                class="badge bg-soft-success text-success">{{ $purchases->total() }}</span>
                                                        @endif
                                                    </a>
                                                </li>

                                                @if ($roleUser == 'instructor')
                                                    <li class="nav-item">
                                                        <a class="nav-link" data-bs-toggle="tab" href="#tab-withdrawals"
                                                            role="tab">
                                                            <i class="mdi mdi-bank-transfer-out me-1"></i> Lịch sử rút tiền
                                                            @if (isset($transactions) && $transactions->count() > 0)
                                                                <span
                                                                    class="badge bg-soft-warning text-warning">{{ $transactions->total() }}</span>
                                                            @endif
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>

                                            <!-- Tab Content -->
                                            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                                                <!-- Tab: Khóa học -->
                                                <div class="tab-pane fade show active" id="tab-courses" role="tabpanel">
                                                    @if ($courses->count() > 0)
                                                        <div class="table-responsive shadow-sm rounded">
                                                            <table class="table table-hover mb-0" id="table-courses">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>STT</th>
                                                                        <th>Tên khóa học</th>
                                                                        @if ($roleUser == 'instructor')
                                                                            <th>Học viên</th>
                                                                            <th>Doanh thu</th>
                                                                            <th>Đánh giá</th>
                                                                        @else
                                                                            <th>Trạng thái</th>
                                                                            <th>Tiến độ học</th>
                                                                            <th>Ngày mua</th>
                                                                        @endif
                                                                        <th>Thao tác</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($courses as $index => $course)
                                                                        <tr>
                                                                            <td>{{ $courses->firstItem() + $index }}</td>
                                                                            <td>
                                                                                <div class="d-flex">
                                                                                    <img src="{{ $course->thumbnail ?? '/images/placeholder.png' }}"
                                                                                        alt="thumbnail"
                                                                                        class="img-thumbnail"
                                                                                        style="width: 80px; height: 50px; object-fit: cover;">
                                                                                    <span style="white-space: pre-line;"
                                                                                        class="ms-1">{{ $course->name }}</span>
                                                                                </div>
                                                                            </td>
                                                                            @if ($roleUser == 'instructor')
                                                                                <td>{{ $course->total_student }}</td>
                                                                                <td>{{ number_format($course->total_revenue) }}
                                                                                    VND</td>
                                                                                @php
                                                                                    $rating =
                                                                                        round($course->avg_rating * 2) /
                                                                                        2;
                                                                                    $fullStars = floor($rating);
                                                                                    $halfStar =
                                                                                        $rating - $fullStars === 0.5;
                                                                                    $emptyStars =
                                                                                        5 -
                                                                                        $fullStars -
                                                                                        ($halfStar ? 1 : 0);
                                                                                @endphp

                                                                                <td>
                                                                                    @for ($i = 0; $i < $fullStars; $i++)
                                                                                        <i
                                                                                            class="fas fa-star text-warning"></i>
                                                                                    @endfor

                                                                                    @if ($halfStar)
                                                                                        <i
                                                                                            class="fas fa-star-half-alt text-warning"></i>
                                                                                    @endif

                                                                                    @for ($i = 0; $i < $emptyStars; $i++)
                                                                                        <i
                                                                                            class="far fa-star text-warning"></i>
                                                                                    @endfor
                                                                                </td>
                                                                            @else
                                                                                <td>
                                                                                    <span
                                                                                        class="badge bg-success text-white">Đã
                                                                                        mua</span>
                                                                                </td>
                                                                                <td>
                                                                                    @if ($course->progress_percent == 100)
                                                                                        <span class="badge bg-primary">Hoàn
                                                                                            thành</span>
                                                                                    @elseif($course->progress_percent < 100 && $course->progress_percent > 0)
                                                                                        <span class="badge bg-warning">Chưa
                                                                                            hoàn
                                                                                            thành</span>
                                                                                    @else
                                                                                        <span class="badge bg-danger">Chưa
                                                                                            học</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td>{{ \Carbon\Carbon::parse($course->created_at)->format('d/m/Y H:i') }}
                                                                                </td>
                                                                            @endif
                                                                            <td>
                                                                                <a href="{{ route('admin.courses.show', $course->id) }}"
                                                                                    class="btn btn-sm btn-info">
                                                                                    <i class="mdi mdi-eye"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="mt-4 d-flex justify-content-center">
                                                            <div id="pagination-links-courses">
                                                                {{ $courses->appends(request()->query())->links() }}
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="alert alert-info">
                                                            <i class="mdi mdi-information-outline me-2"></i>
                                                            @if ($roleUser == 'instructor')
                                                                Không có khóa học nào được tạo bởi giảng viên này.
                                                            @else
                                                                Bạn chưa mua khóa học nào.
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Tab: Gói Membership -->
                                                <div class="tab-pane fade" id="tab-memberships" role="tabpanel">
                                                    @if (isset($memberships) && $memberships->count() > 0)
                                                        <div class="table-responsive shadow-sm rounded">
                                                            <table class="table table-hover mb-0" id="table-memberships">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>STT</th>
                                                                        <th>Tên gói</th>
                                                                        <th>Thời hạn</th>
                                                                        @if ($roleUser == 'instructor')
                                                                            <th>Tổng doanh thu</th>
                                                                            <th>Số người mua</th>
                                                                        @else
                                                                            <th>Ngày mua</th>
                                                                            <th>Ngày hết hạn</th>
                                                                        @endif
                                                                        <th>Thao tác</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($memberships as $index => $membership)
                                                                        <tr>
                                                                            <td>{{ $memberships->firstItem() + $index }}
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex">
                                                                                    <div class="flex-shrink-0 me-2 d-flex">
                                                                                        <img src="{{ asset('assets/images/thumnail_membership.jpg') }}"
                                                                                            class="img-fluid rounded-circle"
                                                                                            style="width: 50px; height: 50px; object-fit: cover;"
                                                                                            alt="" />
                                                                                    </div>
                                                                                    <div>
                                                                                        <h6 class="mb-0"
                                                                                            style="white-space: pre-line;">
                                                                                            {{ $membership->name }}
                                                                                        </h6>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td><code>{{ $membership->duration_months }}
                                                                                    tháng</code></td>
                                                                            @if ($roleUser == 'instructor')
                                                                                <td>{{ number_format($membership->total_revenue) }}
                                                                                    VND</td>
                                                                                <td>{{ $membership->total_bought }} người
                                                                                    mua</td>
                                                                            @else
                                                                                <td>{{ \Carbon\Carbon::parse($membership->created_at)->format('d/m/Y') }}
                                                                                </td>
                                                                                <td>
                                                                                    {{ optional($membership->created_at ? \Carbon\Carbon::parse($membership->created_at) : null)->addMonths($membership->duration_months)
                                                                                        ?->format('d/m/Y') ?? 'Chưa có' }}
                                                                                </td>
                                                                            @endif
                                                                            <td>
                                                                                <a href="{{ route('admin.approvals.memberships.show', $membership->id) }}"
                                                                                    class="btn btn-sm btn-info">
                                                                                    <i class="mdi mdi-eye"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="mt-4 d-flex justify-content-center">
                                                            <div id="pagination-links-memberships">
                                                                {{ $memberships->appends(request()->query())->links() }}
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="alert alert-info">
                                                            <i class="mdi mdi-information-outline me-2"></i>
                                                            @if ($roleUser == 'instructor')
                                                                Giảng viên chưa tạo gói membership nào.
                                                            @else
                                                                Người dùng chưa đăng ký gói membership nào.
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Tab: Lịch sử mua hàng (chỉ cho member) -->
                                                <div class="tab-pane fade" id="tab-purchases" role="tabpanel">
                                                    @if (isset($purchases) && $purchases->count() > 0)
                                                        <div class="table-responsive shadow-sm rounded">
                                                            <table class="table table-hover mb-0" id="table-purchases">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>STT</th>
                                                                        <th>Mã giao dịch</th>
                                                                        <th>Loại</th>
                                                                        <th>Tên sản phẩm</th>
                                                                        <th>Số tiền</th>
                                                                        <th>Phương thức</th>
                                                                        <th>Ngày mua</th>
                                                                        <th>Thao tác</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($purchases as $index => $purchase)
                                                                        <tr>
                                                                            <td>{{ $purchases->firstItem() + $index }}
                                                                            </td>
                                                                            <td><code>{{ $purchase->code }}</code>
                                                                            </td>
                                                                            <td>
                                                                                @if ($purchase->invoice_type == 'course')
                                                                                    <span
                                                                                        class="badge bg-soft-primary text-primary">
                                                                                        <i
                                                                                            class="mdi mdi-book-open-variant me-1"></i>Khóa
                                                                                        học
                                                                                    </span>
                                                                                @elseif($purchase->invoice_type == 'membership')
                                                                                    <span
                                                                                        class="badge bg-soft-info text-info">
                                                                                        <i
                                                                                            class="mdi mdi-certificate me-1"></i>Membership
                                                                                    </span>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @if ($purchase->invoice_type == 'course')
                                                                                    {{ $purchase->course->name }}
                                                                                @elseif($purchase->invoice_type == 'membership')
                                                                                    {{ $purchase->membershipPlan->name }}
                                                                                @endif
                                                                            </td>
                                                                            <td>{{ number_format($purchase->final_amount) }}
                                                                                VND</td>
                                                                            <td>
                                                                                <span class="badge bg-soft-info text-info">
                                                                                    <i
                                                                                        class="mdi mdi-credit-card me-1"></i>{{ $purchase->payment_method }}
                                                                                </span>
                                                                            </td>
                                                                            <td>{{ \Carbon\Carbon::parse($purchase->created_at)->format('d/m/Y H:i') }}
                                                                            </td>
                                                                            <td>
                                                                                @if ($purchase->invoice_type == 'course')
                                                                                    <a href="{{ route('admin.invoices.show', $purchase->code) }}"
                                                                                        class="btn btn-sm btn-info">
                                                                                        <i class="mdi mdi-eye"></i>
                                                                                    </a>
                                                                                @elseif($purchase->invoice_type == 'membership')
                                                                                    <a href="{{ route('admin.invoices.memberships.show', $purchase->code) }}"
                                                                                        class="btn btn-sm btn-info">
                                                                                        <i class="mdi mdi-eye"></i>
                                                                                    </a>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="mt-4 d-flex justify-content-center">
                                                            <div id="pagination-links-purchases">
                                                                {{ $purchases->appends(request()->query())->links() }}
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="alert alert-info">
                                                            <i class="mdi mdi-information-outline me-2"></i>
                                                            Người dùng chưa có giao dịch mua hàng nào.
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Tab: Lịch sử rút tiền (chỉ cho instructor) -->
                                                @if ($roleUser == 'instructor')
                                                    <div class="tab-pane fade" id="tab-withdrawals" role="tabpanel">
                                                        @if (isset($transactions) && $transactions->count() > 0)
                                                            <div class="table-responsive shadow-sm rounded">
                                                                <table class="table table-hover mb-0"
                                                                    id="table-withdrawals">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>STT</th>
                                                                            <th>Mã giao dịch</th>
                                                                            <th>Số tiền</th>
                                                                            <th>Phương thức</th>
                                                                            <th>Trạng thái</th>
                                                                            <th>Ngày yêu cầu</th>
                                                                            <th>Thao tác</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($transactions as $index => $transaction)
                                                                            <tr>
                                                                                <td>{{ $transactions->firstItem() + $index }}
                                                                                </td>
                                                                                <td><code>{{ $transaction->transaction_code }}</code>
                                                                                </td>
                                                                                <td>{{ number_format($transaction->amount) }}
                                                                                    VND</td>
                                                                                <td>
                                                                                    <span
                                                                                        class="badge bg-soft-primary text-primary">
                                                                                        <i
                                                                                            class="mdi mdi-bank me-1"></i>Chuyển
                                                                                        khoản
                                                                                    </span>
                                                                                </td>
                                                                                <td>
                                                                                    @if ($transaction->transactionable->status == 'Đã xử lý')
                                                                                        <span
                                                                                            class="badge bg-soft-success text-success">Đã
                                                                                            xử lý</span>
                                                                                    @elseif($transaction->transactionable->status == 'Từ chối')
                                                                                        <span
                                                                                            class="badge bg-soft-danger text-danger">Từ
                                                                                            chối</span>
                                                                                    @else
                                                                                        <span
                                                                                            class="badge bg-soft-secondary text-secondary">{{ $transaction->transactionable->status }}</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}
                                                                                </td>
                                                                                <td>
                                                                                    <a href="{{ route('admin.withdrawals.show', $transaction->transactionable->id) }}"
                                                                                        class="btn btn-sm btn-info">
                                                                                        <i class="mdi mdi-eye"></i>
                                                                                    </a>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <div class="mt-4 d-flex justify-content-center">
                                                                <div id="pagination-links-withdrawals">
                                                                    {{ $transactions->appends(request()->query())->links() }}
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="alert alert-info">
                                                                <i class="mdi mdi-information-outline me-2"></i>
                                                                Người dùng chưa có giao dịch rút tiền nào.
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div> --}}
                                        </div>

                                    <div class="action-buttons">
                                        <a href="{{ route('admin.users.index') }}"
                                            class="btn btn-light">
                                            <i class="mdi mdi-arrow-left"></i>Quay lại
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning">
                                            <i class="mdi mdi-pencil"></i>Chỉnh sửa
                                        </a>
                                        <button type="button" class="btn btn-info">
                                            <i class="mdi mdi-history"></i>Lịch sử hoạt động
                                        </button>
                                        <button type="button" class="btn btn-danger">
                                            <i class="mdi mdi-delete"></i>Xóa tài khoản
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    {{-- <script>
        $(document).ready(function() {
            $(document).on('click', '#pagination-links-courses a', function(e) {
                e.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadTabContent('courses', page);
            });

            $(document).on('click', '#pagination-links-memberships a', function(e) {
                e.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadTabContent('memberships', page);
            });

            $(document).on('click', '#pagination-links-purchases a', function(e) {
                e.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadTabContent('purchases', page);
            });

            $(document).on('click', '#pagination-links-withdrawals a', function(e) {
                e.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadTabContent('withdrawals', page);
            });

            var currentTab = 'courses';

            function loadTabContent(tabType, page) {
                $.ajax({
                    url: "{{ route('admin.users.show', $user->id) }}",
                    type: "GET",
                    data: {
                        type: tabType,
                        page: page
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $('#table-' + tabType + ' tbody').html(
                            '<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>'
                        );
                    },
                    success: function(data) {
                        $('#table-' + tabType + ' tbody').html(data[tabType + '_table']);
                        $('#pagination-links-' + tabType).html(data['pagination_links_' + tabType]);
                    },
                    error: function(xhr, status, error) {
                        $('#table-' + tabType + ' tbody').html(
                            '<tr><td colspan="8" class="text-center">Không có dữ liệu hiển thị</td></tr>'
                        );

                        toastr.error('Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.', 'Lỗi!');
                        console.error('Ajax_loi', error)
                    }
                });
            }
        });
    </script> --}}
@endpush
