@extends('admin.layouts.app')
@push('page-css')
    <link href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        #couponCode {
            text-transform: uppercase;
        }

        #couponCode::placeholder {
            text-transform: none;
        }

        .form-section {
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }

        .suggestion-item {
            display: inline-block;
            margin-right: 5px;
            background-color: #f1f5ff;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .suggestion-item:hover {
            background-color: #3b82f6;
            color: white !important;
        }

        .btn-submit {
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.15rem rgba(59, 130, 246, 0.25);
        }

        .flatpickr-months .flatpickr-month {
            background: #3F508C;
            color: white;
        }

        .flatpickr-current-month input.cur-year {
            color: white !important;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            color: white !important;
        }

        .flatpickr-day.today {
            border-color: #3F508C;
            background: #3F508C;
            color: white;
        }

        .flatpickr-day.selected {
            background: #3F508C !important;
            color: white !important;
        }

        #user_selection_section {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }

        .user-list-item {
            display: flex;
            align-items: center;
            background-color: #ffffff;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .user-list-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .user-list-item img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 16px;
            border: 2px solid #e5e7eb;
        }
    </style>
@endpush

@php
    $title = 'Thêm mới coupon';
@endphp

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Quản lí mã giảm giá</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Danh sách mã giảm giá</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <form action="{{ route('admin.coupons.store') }}" method="post" id="couponForm">
            @csrf
            <div class="row">
                <div class="col-lg-8">
                    <div class="form-section">
                        <div class="section-title">Thông Tin Cơ Bản</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên Chương Trình <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="mdi mdi-tag-text-outline"></i></span>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                        placeholder="Nhập tên chương trình" required>
                                </div>
                                @error('name')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mã Giảm Giá <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="mdi mdi-barcode"></i></span>
                                    <input type="text" name="code" id="couponCode"
                                        class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                                        placeholder="Nhập mã giảm giá" required>
                                    <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                        <i class="mdi mdi-refresh"></i>
                                    </button>
                                </div>
                                @error('code')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ngày Bắt Đầu <span class="text-danger">*</span></label>
                                <input type="text" name="start_date"
                                    class="form-control flatpickr-date @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date') }}" placeholder="Chọn ngày bắt đầu" required>
                                @error('start_date')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ngày Kết Thúc <span class="text-danger">*</span></label>
                                <input type="text" name="expire_date"
                                    class="form-control flatpickr-date @error('expire_date') is-invalid @enderror"
                                    value="{{ old('expire_date') }}" placeholder="Chọn ngày kết thúc" required>
                                @error('expire_date')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Số Lượng Sử Dụng <span class="text-danger">*</span></label>
                                <input type="number" name="max_usage"
                                    class="form-control @error('max_usage') is-invalid @enderror"
                                    value="{{ old('max_usage') }}" placeholder="Nhập số lượng sử dụng" min="1"
                                    required>
                                @error('max_usage')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Cấu Hình Giảm Giá</div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Loại Giảm Giá <span class="text-danger">*</span></label>
                                <select name="discount_type" id="discount_type"
                                    class="form-select @error('discount_type') is-invalid @enderror" required>
                                    <option value="">Chọn loại giảm giá</option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Cố Định
                                        (VNĐ)</option>
                                    <option value="percentage"
                                        {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Phần Trăm (%)</option>
                                </select>
                                @error('discount_type')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-4 discount-value-field" style="display: none;">
                                <label class="form-label">Giá Trị Giảm Giá <span class="text-danger">*</span></label>
                                <input type="number" name="discount_value"
                                    class="form-control @error('discount_value') is-invalid @enderror"
                                    value="{{ old('discount_value') }}" placeholder="Nhập giá trị" min="0">
                                @error('discount_value')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-4 discount-max-value-field" style="display: none;">
                                <label class="form-label">Giảm Giá Tối Đa <span class="text-danger">*</span></label>
                                <input type="number" name="discount_max_value"
                                    class="form-control @error('discount_max_value') is-invalid @enderror"
                                    value="{{ old('discount_max_value') }}" placeholder="Nhập giá trị tối đa"
                                    min="0">
                                @error('discount_max_value')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Trạng Thái <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">Chọn trạng thái</option>
                                    <option value="1" {{ old('status') == '1' ? 'selected' : '' }} selected>Hoạt Động
                                    </option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Không Hoạt Động
                                    </option>
                                </select>
                                @error('status')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Phạm Vi Áp Dụng</div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="system_wide_coupon" name="system_wide"
                                value="{{ old('system_wide', 'true') }}"
                                {{ old('system_wide', 'true') == 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="system_wide_coupon">
                                Áp Dụng Cho Toàn Bộ Hệ Thống
                            </label>
                        </div>

                        <div id="user_selection_section" style="display:none;">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Chọn Người Dùng</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Tìm Kiếm Người Dùng</label>
                                            <select id="user_select" class="form-control" multiple
                                                name="selected_users[]">
                                            </select>
                                        </div>
                                    </div>

                                    <div id="selected_users_list" class="mt-3">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Mô Tả</div>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4"
                            placeholder="Nhập mô tả về mã giảm giá">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="card-title mb-0">Thao Tác</h5>
                        </div>
                        <div class="card-body p-4">
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="mdi mdi-content-save me-2"></i>Lưu Mã Giảm Giá
                            </button>
                            <button type="reset" class="btn btn-outline-secondary w-100">
                                <i class="mdi mdi-refresh me-2"></i>Làm Mới
                            </button>
                            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-info w-100 mt-3">
                                <i class="mdi mdi-arrow-left me-2"></i>Quay Lại
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-gradient-info text-white">
                            <h5 class="card-title mb-0">Thông Tin Hỗ Trợ</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start mb-3">
                                <i class="mdi mdi-lightbulb-outline text-warning me-2 fs-4"></i>
                                <p class="text-muted mb-0">Đảm bảo điền đầy đủ thông tin để tạo mã giảm giá hiệu quả.</p>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="mdi mdi-information-outline text-info me-2 fs-4"></i>
                                <p class="text-muted mb-0">Mã giảm giá sẽ được áp dụng ngay sau khi lưu.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/common/common.js') }}"></script>
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $(".flatpickr-date").flatpickr({
                dateFormat: "Y-m-d",
                minDate: "today",
                theme: "material_blue"
            });

            function updateDiscountFields() {
                let discountType = $('#discount_type').val();
                $('.discount-value-field, .discount-max-value-field').hide();
                if (discountType === 'fixed') {
                    $('.discount-value-field').show();
                } else if (discountType === 'percentage') {
                    $('.discount-value-field, .discount-max-value-field').show();
                }
            }
            $('#discount_type').change(updateDiscountFields);
            updateDiscountFields();

            $('#system_wide_coupon').change(function() {
                let isChecked = $(this).prop('checked');

                $(this).val(isChecked);

                $('#user_selection_section').toggle(!isChecked);

                if (!isChecked) {
                    $('#user_select').attr('required', 'required');
                    $('#user_select').closest('.form-group').find('.error-message').remove();

                    // Check if any users are selected
                    if ($('#user_select').val() === null || $('#user_select').val().length === 0) {
                        $('#user_select').closest('.form-group').append(
                            '<div class="text-danger error-message mt-1 small">Vui lòng chọn ít nhất một người dùng nếu không áp dụng toàn hệ thống.</div>'
                        );
                    }
                } else {
                    // If system wide, remove required attribute and any error messages
                    $('#user_select').removeAttr('required');
                    $('#user_select').closest('.form-group').find('.error-message').remove();
                }
            });

            // Trigger change event on page load to set initial state
            $('#system_wide_coupon').trigger('change');

            $('#user_select').select2({
                placeholder: 'Chọn người dùng',
                allowClear: true,
                multiple: true,
                maximumSelectionLength: 50,
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('admin.coupons.search') }}",
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.users ? data.users.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name + ' (' + item.email + ')',
                                    name: item.name,
                                    email: item.email,
                                    avatar: item.avatar
                                };
                            }) : [],
                            pagination: {
                                more: data.pagination && (params.page * 10) < data.pagination.total
                            }
                        };
                    },
                    cache: true
                },
                language: {
                    searching: function() {
                        return 'Đang tìm kiếm...';
                    },
                    noResults: function() {
                        return 'Không tìm thấy người dùng';
                    },
                    inputTooShort: function() {
                        return 'Vui lòng nhập dữ liệu để tìm kiếm...';
                    },
                    maximumSelected: function(args) {
                        return 'Bạn chỉ có thể chọn tối đa ' + args.maximum + ' người dùng';
                    }
                },
                templateResult: formatUser,
                templateSelection: formatUserSelection,
                escapeMarkup: function(markup) {
                    return markup;
                }
            });

            $('#couponForm').on('submit', function(e) {
                let isSystemWide = $('#system_wide_coupon').prop('checked');
                let selectedUsers = $('#user_select').val();

                if (!isSystemWide && (!selectedUsers || selectedUsers.length === 0)) {
                    e.preventDefault();
                    $('#user_select').closest('.form-group').append(
                        '<div class="text-danger error-message mt-1 small">Vui lòng chọn ít nhất một người dùng nếu không áp dụng toàn hệ thống.</div>'
                    );
                }
            });

            function formatUser(user) {
                if (!user.id) {
                    return user.text;
                }
                var avatarUrl = user.avatar ? user.avatar :
                    'https://res.cloudinary.com/dvrexlsgx/image/upload/v1732148083/Avatar-trang-den_apceuv_pgbce6.png';
                var $user = $(
                    '<span style="display: flex; align-items: center;">' +
                    '<img src="' + avatarUrl +
                    '" class="rounded-circle me-2" style="width: 30px; height: 30px;" />' +
                    '<span>' + user.text + '</span>' +
                    '</span>'
                );
                return $user;
            }

            function formatUserSelection(user) {
                if (!user.id) {
                    return user.text;
                }
                var avatarUrl = user.avatar ? user.avatar :
                    'https://res.cloudinary.com/dvrexlsgx/image/upload/v1732148083/Avatar-trang-den_apceuv_pgbce6.png';
                var $user = $(
                    '<span style="display: inline-flex; align-items: center;">' +
                    '<img src="' + avatarUrl +
                    '" class="rounded-circle me-1" style="width: 20px; height: 20px;" />' +
                    '<span>' + user.text + '</span>' +
                    '</span>'
                );
                return $user;
            }

            $('#generateCode').on('click', function() {
                const randomCode = generateRandomCouponCode();
                $('#couponCode').val(randomCode);
            });

            $('#suggestCode').hide();

            $(document).on('input', '#couponCode', function() {
                let data = $(this).val();

                if (data.length < 3) {
                    $('#suggestCode').hide();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.coupons.suggestCode') }}",
                    type: 'GET',
                    data: {
                        code: data
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.length > 0) {
                            let suggestionsHtml =
                                '<div class="suggestion-container mt-2"><i class="mdi mdi-lightbulb-outline text-warning"></i> Gợi ý: ';
                            response.forEach(function(code) {
                                suggestionsHtml += `<span class="suggestion-item p-2 text-primary"
                                            style="font-size: 0.85em !important; cursor: pointer;">
                                            ${code}
                                        </span>`;
                            });
                            suggestionsHtml += '</div>';

                            $('#suggestCode').html(suggestionsHtml).show();
                        } else {
                            $('#suggestCode').hide();
                        }
                    }
                });
            });

            $(document).on('click', '.suggestion-item', function() {
                $('#couponCode').val($(this).text().trim());
                $('#suggestCode').hide();
            });
        });
    </script>
@endpush
