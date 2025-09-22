<!-- Modal Thêm Kho Hàng -->
<div class="modal fade" id="addWarehouse" tabindex="-1" aria-labelledby="addWarehouseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWarehouseLabel">Thêm kho hàng mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.warehouses.store') }}" method="POST" id="addWarehouseForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Thông tin cơ bản -->
                        <div class="col-12">
                            <h6 class="mb-3 fw-semibold text-uppercase">
                                <i class="ri-information-line me-1"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="codeInput" class="form-label">Mã kho <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       id="codeInput" name="code" placeholder="VD: WH001" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="nameInput" class="form-label">Tên kho <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="nameInput" name="name" placeholder="Nhập tên kho hàng" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="typeSelect" class="form-label">Loại kho <span class="text-danger">*</span></label>
                                <select class="form-control @error('type') is-invalid @enderror" 
                                        id="typeSelect" name="type" required>
                                    <option value="">-- Chọn loại kho --</option>
                                    <option value="Main">Kho chính</option>
                                    <option value="Branch">Kho chi nhánh</option>
                                    <option value="Temporary">Kho tạm</option>
                                    <option value="Distribution">Kho phân phối</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="phoneInput" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phoneInput" name="phone" placeholder="0123456789" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="emailInput" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="emailInput" name="email" placeholder="warehouse@example.com" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Địa chỉ -->
                        <div class="col-12 mt-3">
                            <h6 class="mb-3 fw-semibold text-uppercase">
                                <i class="ri-map-pin-line me-1"></i>Địa chỉ kho hàng
                            </h6>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="addressInput" class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                       id="addressInput" name="address" 
                                       placeholder="Số nhà, tên đường..." required>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="districtInput" class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('district') is-invalid @enderror" 
                                       id="districtInput" name="district" placeholder="Nhập quận/huyện" required>
                                @error('district')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="cityInput" class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="cityInput" name="city" placeholder="Nhập tỉnh/thành phố" required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Người quản lý -->
                        <div class="col-12 mt-3">
                            <h6 class="mb-3 fw-semibold text-uppercase">
                                <i class="ri-user-3-line me-1"></i>Thông tin người quản lý
                            </h6>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="managerNameInput" class="form-label">Tên người quản lý <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('manager_name') is-invalid @enderror" 
                                       id="managerNameInput" name="manager_name" 
                                       placeholder="Nhập tên người quản lý" required>
                                @error('manager_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="managerPhoneInput" class="form-label">SĐT người quản lý <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('manager_phone') is-invalid @enderror" 
                                       id="managerPhoneInput" name="manager_phone" 
                                       placeholder="0123456789" required>
                                @error('manager_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Ghi chú -->
                        <div class="col-12 mt-3">
                            <h6 class="mb-3 fw-semibold text-uppercase">
                                <i class="ri-file-text-line me-1"></i>Thông tin bổ sung
                            </h6>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="notesTextarea" class="form-label">Ghi chú</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notesTextarea" name="notes" rows="3" 
                                          placeholder="Nhập ghi chú về kho hàng (nếu có)"></textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="isActiveSwitch" name="is_active" checked>
                                <label class="form-check-label" for="isActiveSwitch">
                                    Kích hoạt kho hàng ngay sau khi tạo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1 align-middle"></i> Đóng
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveWarehouseBtn">
                        <i class="ri-save-3-line me-1 align-middle"></i> Lưu kho hàng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate form trước khi submit
    const form = document.getElementById('addWarehouseForm');
    const saveBtn = document.getElementById('saveWarehouseBtn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Disable button và hiển thị loading
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang lưu...';
        
        // Submit form
        this.submit();
    });
    
    // Auto generate mã kho
    document.getElementById('nameInput').addEventListener('input', function() {
        const name = this.value;
        const codeInput = document.getElementById('codeInput');
        
        if (!codeInput.value && name) {
            // Tạo mã tự động từ tên
            const words = name.split(' ');
            let code = 'WH';
            
            if (words.length > 1) {
                code += words.map(word => word.charAt(0).toUpperCase()).join('');
            } else {
                code += name.substring(0, 3).toUpperCase();
            }
            
            // Thêm số ngẫu nhiên
            code += Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            
            codeInput.value = code;
        }
    });
    
    // Format số điện thoại
    const phoneInputs = [document.getElementById('phoneInput'), document.getElementById('managerPhoneInput')];
    
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });
    });
    
    // Reset form khi đóng modal
    const modal = document.getElementById('addWarehouse');
    modal.addEventListener('hidden.bs.modal', function() {
        form.reset();
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="ri-save-3-line me-1 align-middle"></i> Lưu kho hàng';
        
        // Clear validation errors
        const invalidInputs = form.querySelectorAll('.is-invalid');
        invalidInputs.forEach(input => {
            input.classList.remove('is-invalid');
        });
        
        const invalidFeedbacks = form.querySelectorAll('.invalid-feedback');
        invalidFeedbacks.forEach(feedback => {
            feedback.remove();
        });
    });
});
</script>