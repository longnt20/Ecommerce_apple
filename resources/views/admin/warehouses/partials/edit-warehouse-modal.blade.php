<!-- Modal Sửa Kho Hàng -->
<div class="modal fade" id="editWarehouse" tabindex="-1" aria-labelledby="editWarehouseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editWarehouseLabel">Cập nhật thông tin kho hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="editWarehouseForm">
                @csrf
                @method('PUT')
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
                                <label for="editCodeInput" class="form-label">Mã kho <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editCodeInput" name="code">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="editNameInput" class="form-label">Tên kho <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNameInput" name="name">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="editTypeSelect" class="form-label">Loại kho <span class="text-danger">*</span></label>
                                <select class="form-control" id="editTypeSelect" name="type">
                                    <option value="">-- Chọn loại kho --</option>
                                    <option value="Main">Kho chính</option>
                                    <option value="Branch">Kho chi nhánh</option>
                                    <option value="Temporary">Kho tạm</option>
                                    <option value="Distribution">Kho phân phối</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="editPhoneInput" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editPhoneInput" name="phone">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="editEmailInput" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="editEmailInput" name="email">
                                <div class="invalid-feedback"></div>
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
                                <label for="editAddressInput" class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editAddressInput" name="address">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="editDistrictInput" class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editDistrictInput" name="district">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="editCityInput" class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editCityInput" name="city">
                                <div class="invalid-feedback"></div>
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
                                <label for="editManagerNameInput" class="form-label">Tên người quản lý <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editManagerNameInput" name="manager_name">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="editManagerPhoneInput" class="form-label">SĐT người quản lý <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editManagerPhoneInput" name="manager_phone">
                                <div class="invalid-feedback"></div>
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
                                <label for="editNotesTextarea" class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="editNotesTextarea" name="notes" rows="3"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="editIsActiveSwitch" name="is_active" value="1">
                                <label class="form-check-label" for="editIsActiveSwitch">
                                    Kho hàng đang hoạt động
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1 align-middle"></i> Đóng
                    </button>
                    <button type="submit" class="btn btn-primary" id="updateWarehouseBtn">
                        <i class="ri-save-3-line me-1 align-middle"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>