<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'short_description' => 'nullable|max:500',
            'description' => 'nullable',
            'category_id' => 'required|exists:categories,id',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'default_price' => 'nullable|numeric',
            'visibility' => 'required|in:public,hidden',
            'status' => 'required|in:published,draft',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'required_with:variants.*.price|string|max:255',
            'variants.*.price' => 'required_with:variants.*.sku|numeric',
            'variants.*.thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Tên sản phẩm không được để trống',
            'name.max'      => 'Tên sản phẩm không được quá 255 kí tự',
            'short_description.max' => 'Mô tả ngắn không được vượt quá 255 kí tự',
            'category_id.required'  => 'Danh mục không được để trống',
            'category_id.exists'    => 'Danh mục không tồn tại',
            'thumbnail.image'       => 'Không đúng định dạng ảnh',
            'thumbnail.mimes'       => 'Không đúng loại ảnh được upload',
            'thumbnail.max'         => 'Ảnh không được quá 2048MB',
            'gallery.image'       => 'Không đúng định dạng ảnh',
            'gallery.mimes'       => 'Không đúng loại ảnh được upload',
            'gallery.max'         => 'Ảnh không được quá 2048MB',
            'default_price.numeric' => 'Giá phải là số',
            'visibility.required'   => 'Trạng thái hiển thị là bắt buộc',
            'status.required'       => 'Trạng thái là bắt buộc',
            'variants.array' => 'Dữ liệu biến thể không hợp lệ.',

            // SKU
            'variants.*.sku.required_with' => 'Vui lòng nhập mã SKU cho biến thể khi đã có giá.',
            'variants.*.sku.string' => 'Mã SKU phải là chuỗi ký tự.',
            'variants.*.sku.max' => 'Mã SKU không được vượt quá 255 ký tự.',

            // PRICE
            'variants.*.price.required_with' => 'Vui lòng nhập giá cho biến thể khi đã có mã SKU.',
            'variants.*.price.numeric' => 'Giá biến thể phải là số.',

            // THUMBNAIL
            'variants.*.thumbnail.image' => 'Ảnh biến thể phải là định dạng hình ảnh.',
            'variants.*.thumbnail.mimes' => 'Ảnh biến thể chỉ chấp nhận các định dạng: jpeg, png, jpg, webp, gif.',
            'variants.*.thumbnail.max' => 'Ảnh biến thể không được vượt quá 2MB.',
        ];
    }
}
