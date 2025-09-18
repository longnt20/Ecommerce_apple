<?php

namespace App\Http\Requests\Admin\Variant;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|unique:product_variants,sku',
            'price' => 'required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cost_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|unique:product_variants,barcode',
            'color' => 'nullable|string',
            'storage' => 'nullable|string',
        ];
    }
    public function messages()
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm',
            'product_id.exists'   => 'Sản phẩm không tồn tại',
            'sku.required'        => 'Vui lòng nhập mã sản phẩm',
            'sku.unique'          => 'Mã sản phẩm đã tồn tại',
            'price.required'        => 'Vui lòng nhập giá sản phẩm',
            'price.numeric'       => 'Giá sản phẩm phải là số',
            'price.min'           => 'Giá sản phẩm phải lớn hơn 0',
            'cost_price.numeric'       => 'Giá khuyến mãi phải là số',
            'cost_price.min'           => 'Giá khuyến mãi phải lớn hơn 0',
            'barcode.string'           => 'Barcode phải là một chuỗi kí tự',
            'barcode.unique'           => 'Barcode đã tồn tại', 
        ];
    }
}
