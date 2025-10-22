<?php

namespace App\Http\Requests\Admin\Banner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
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
            'title' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'order' => 'nullable|integer',
            'content'=>'nullable|string|max:255',
            'status'      => [Rule::in(0,1)],
        ];
    }
    public function messages()
    {
        return [
            'title.required' =>'Tiêu đề không được để trống',
            'title.max'=>'Tiêu đề không được quá 255 kí tự',
            'order.interger'=>'Order phải là sô nguyên',
            'content.string'=>'Content phải là chuỗi kí tự',
            'content.max'=>'Content không được quá 255 kí tự',
            'status.in'     => 'Trạng thái không hợp lệ',
        ];
    }
}
