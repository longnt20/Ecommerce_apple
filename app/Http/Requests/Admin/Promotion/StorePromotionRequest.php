<?php

namespace App\Http\Requests\Admin\Promotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'frame_id' => 'required|exists:frames,id',
            'thumbnail' => 'nullable|image|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:0,1',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|numeric',
            'items.*.item_type' => [
                'required',
                Rule::in([
                    \App\Models\Product::class,
                    \App\Models\ProductVariant::class,
                ]),
            ],
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Tên chương trình khuyến mãi là bắt buộc',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc',
            'end_date.required' => 'Ngày kết thúc là bắt buộc',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
            'items.required' => 'Phải chọn ít nhất 1 sản phẩm',
        ];
    }
}
