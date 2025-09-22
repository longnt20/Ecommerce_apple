@extends('admin.layouts.app')
@section('title', 'Thêm mới thuộc tính')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Thêm thuộc tính mới</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.product-attributes.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Loại thuộc tính <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror">
                                    <option value="">-- Chọn loại --</option>
                                    @foreach ($types as $key => $label)
                                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Giá trị <span class="text-danger">*</span></label>
                                <input type="text" name="value"
                                    class="form-control @error('value') is-invalid @enderror" value="{{ old('value') }}"
                                    placeholder="VD: Black, 128GB">
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nhãn hiển thị <span class="text-danger">*</span></label>
                                <input type="text" name="label"
                                    class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}"
                                    placeholder="VD: Đen, 128GB">
                                @error('label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3" id="hex-code-wrapper" style="display: none;">
                                <label class="form-label">Mã màu (HEX)</label>
                                <div class="input-group">
                                    <!-- Color picker -->
                                    <input type="color" id="colorPicker" class="form-control form-control-color"
                                        value="{{ old('hex_code', '#000000') }}">

                                    <!-- Input text để nhập thủ công -->
                                    <input type="text" name="hex_code" id="hexInput"
                                        class="form-control @error('hex_code') is-invalid @enderror"
                                        value="{{ old('hex_code', '#000000') }}" placeholder="#000000">
                                </div>
                                @error('hex_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thứ tự sắp xếp</label>
                                <input type="number" name="sort_order"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    value="{{ old('sort_order', 0) }}">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                                        value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Kích hoạt
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line"></i> Lưu
                                </button>
                                <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.querySelector('select[name="type"]');
    const hexWrapper = document.getElementById('hex-code-wrapper');
    const colorPicker = document.getElementById('colorPicker');
    const hexInput = document.getElementById('hexInput');

    function toggleHexInput() {
        hexWrapper.style.display = (typeSelect.value === 'color') ? 'block' : 'none';
    }

    // đồng bộ khi chọn trên color picker
    colorPicker.addEventListener('input', function () {
        hexInput.value = colorPicker.value;
    });

    // đồng bộ khi nhập thủ công
    hexInput.addEventListener('input', function () {
        let val = hexInput.value;
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            colorPicker.value = val;
        }
    });

    toggleHexInput();
    typeSelect.addEventListener('change', toggleHexInput);
});
</script>

@endpush
