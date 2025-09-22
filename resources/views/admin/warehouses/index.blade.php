@extends('admin.layouts.app')
@section('title', 'Danh sách kho hàng')
@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Quản lí kho hàng</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Quản lí kho hàng</a></li>
                            <li class="breadcrumb-item active">Danh sách kho hàng</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header border-0 rounded">
                <div class="row g-2">
                    <div class="col-xl-3">
                        <div class="search-box">
                            <input type="text" class="form-control" autocomplete="off" id="searchWarehouse"
                                placeholder="Tìm kiếm kho hàng, mã kho, người quản lý...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <!--end col-->
                    <div class="col-xxl-3 ms-auto">
                        <div>
                            <select class="form-control" id="type-select">
                                <option value="All">Tất cả loại kho</option>
                                <option value="main">Kho chính</option>
                                <option value="branch">Kho chi nhánh</option>
                                <option value="temporary">Kho tạm</option>
                                <option value="distribution">Kho phân phối</option>
                            </select>
                        </div>
                    </div>
                    <!--end col-->
                    <div class="col-lg-auto">
                        <div class="hstack gap-2">
                            <button type="button" class="btn btn-danger">
                                <i class="ri-equalizer-fill me-1 align-bottom"></i> Lọc
                            </button>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addWarehouse">
                                <i class="ri-add-fill me-1 align-bottom"></i> Thêm kho hàng
                            </button>
                        </div>
                    </div>
                    <!--end col-->
                </div>
                <!--end row-->
            </div>
        </div>

        <div class="row mt-4" id="warehouse-list">
            @include('admin.warehouses.partials.warehouse-list')
        </div>
        <!--end row-->

        <div class="row align-items-center mb-4 text-center text-sm-start" id="pagination-element">
            <div class="col-sm">
                <div class="text-muted">
                    Hiển thị {{ $warehouses->firstItem() ?? 0 }} đến {{ $warehouses->lastItem() ?? 0 }}
                    trong tổng số {{ $warehouses->total() }} kho hàng
                </div>
            </div>
            <div class="col-sm-auto mt-3 mt-sm-0">
                {{ $warehouses->links() }}
            </div>
        </div>

        <div id="noresult" class="d-none">
            <div class="text-center py-4">
                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                    colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px"></lord-icon>
                <h5 class="mt-2">Không tìm thấy kết quả!</h5>
                <p class="text-muted mb-0">Chúng tôi không tìm thấy kho hàng nào phù hợp với tìm kiếm của bạn.</p>
            </div>
        </div>

        <!-- Modal Thêm Kho Hàng -->
        @include('admin.warehouses.partials.add-warehouse-modal')
        <!-- Modal Sửa Kho Hàng -->
        @include('admin.warehouses.partials.edit-warehouse-modal')
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Tìm kiếm
            let searchTimeout;
            $('#searchWarehouse').on('keyup', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                const type = $('#type-select').val();

                searchTimeout = setTimeout(function() {
                    searchWarehouses(query, type);
                }, 500);
            });

            // Lọc theo loại
            $('#type-select').on('change', function() {
                const query = $('#searchWarehouse').val();
                const type = $(this).val();
                searchWarehouses(query, type);
            });

            function searchWarehouses(query, type) {
                $.ajax({
                    url: '{{ route('admin.warehouses.search') }}',
                    method: 'GET',
                    data: {
                        query: query,
                        type: type
                    },
                    success: function(response) {
                        $('#warehouse-list').html(response.html);

                        if (response.warehouses.data.length === 0) {
                            $('#noresult').removeClass('d-none');
                            $('#pagination-element').addClass('d-none');
                        } else {
                            $('#noresult').addClass('d-none');
                            $('#pagination-element').removeClass('d-none');
                        }
                    }
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-active').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    let id = this.dataset.id;

                    fetch(`http://127.0.0.1:8000/admin/warehouses/${id}/toggle-active`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({})
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.nextElementSibling.innerText = data.status;
                            }
                        });
                });
            });
        });
    </script>
    <script>
// Function để mở modal edit
function editWarehouse(warehouseId) {
    // Reset form và clear errors
    resetEditForm();
    
    // Lấy thông tin kho hàng
    $.ajax({
        url: `http://127.0.0.1:8000/admin/warehouses/edit/${warehouseId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const warehouse = response.warehouse;
                
                // Set action URL cho form
                $('#editWarehouseForm').attr('action', `http://127.0.0.1:8000/admin/warehouses/${warehouseId}`);
                
                // Fill dữ liệu vào form
                $('#editCodeInput').val(warehouse.code);
                $('#editNameInput').val(warehouse.name);
                $('#editTypeSelect').val(warehouse.type);
                $('#editPhoneInput').val(warehouse.phone);
                $('#editEmailInput').val(warehouse.email);
                $('#editAddressInput').val(warehouse.address);
                $('#editDistrictInput').val(warehouse.district);
                $('#editCityInput').val(warehouse.city);
                $('#editManagerNameInput').val(warehouse.manager_name);
                $('#editManagerPhoneInput').val(warehouse.manager_phone);
                $('#editNotesTextarea').val(warehouse.notes || '');
                $('#editIsActiveSwitch').prop('checked', warehouse.is_active);
                
                // Mở modal
                $('#editWarehouse').modal('show');
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không thể lấy thông tin kho hàng',
                confirmButtonColor: '#d33'
            });
        }
    });
}

// Reset form edit
function resetEditForm() {
    $('#editWarehouseForm')[0].reset();
    $('#editWarehouseForm').find('.is-invalid').removeClass('is-invalid');
    $('#editWarehouseForm').find('.invalid-feedback').text('');
}

// Xử lý submit form edit
$('#editWarehouseForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const submitBtn = $('#updateWarehouseBtn');
    
    // Disable button và hiển thị loading
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang cập nhật...');
    
    // Clear previous errors
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').text('');
    
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            // Đóng modal
            $('#editWarehouse').modal('hide');
            
            // Hiển thị thông báo thành công
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: 'Kho hàng đã được cập nhật thành công',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                // Reload trang
                window.location.reload();
            });
        },
        error: function(xhr) {
            // Enable lại button
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="ri-save-3-line me-1 align-middle"></i> Cập nhật');
            
            if (xhr.status === 422) {
                // Validation errors
                const errors = xhr.responseJSON.errors;
                
                $.each(errors, function(field, messages) {
                    const input = form.find(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(messages[0]);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra, vui lòng thử lại',
                    confirmButtonColor: '#d33'
                });
            }
        }
    });
});

// Format số điện thoại trong modal edit
$('#editPhoneInput, #editManagerPhoneInput').on('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    e.target.value = value;
});

// Reset form khi đóng modal
$('#editWarehouse').on('hidden.bs.modal', function() {
    resetEditForm();
});
</script>
@endpush
