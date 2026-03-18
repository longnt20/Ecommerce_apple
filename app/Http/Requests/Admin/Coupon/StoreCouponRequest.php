<?php

namespace App\Http\Requests\Admin\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
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
            'code' => 'required|string|max:255|unique:coupons,code',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric',
            'discount_max_value' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'expire_date' => 'required|date|after:start_date',
            'max_usage' => 'nullable|integer',
            'status' => 'boolean',
            'system_wide' => 'required|boolean',
            'selected_users' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if (request()->input('system_wide') == false && empty($value)) {
                        $fail('Vui lòng chọn ít nhất một người dùng nếu không áp dụng toàn hệ thống.');
                    }
                },
            ],
            'selected_users.*' => 'exists:users,id',
        ];
    }

    /**
     * Get the custom error messages for the validator.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.string' => 'Trường tên phải là chuỗi ký tự.',
            'name.max' => 'Trường tên không được vượt quá 255 ký tự.',

            'code.required' => 'Trường mã giảm giá là bắt buộc.',
            'code.string' => 'Trường mã giảm giá phải là chuỗi ký tự.',
            'code.max' => 'Trường mã giảm giá không được vượt quá 255 ký tự.',
            'code.unique' => 'Mã giảm giá đã tồn tại trong hệ thống.',

            'discount_type.required' => 'Trường loại giảm giá là bắt buộc.',
            'discount_type.in' => 'Trường loại giảm giá không hợp lệ.',

            'discount_value.required' => 'Trường giá trị giảm giá là bắt buộc.',
            'discount_value.numeric' => 'Trường giá trị giảm giá phải là số.',

            'discount_max_value.numeric' => 'Trường giá trị giảm giá tối đa phải là số.',
            'discount_max_value.min' => 'Trường giá trị giảm giá tối đa phải lớn hơn hoặc bằng 0.',

            'description.string' => 'Trường mô tả phải là chuỗi ký tự.',

            'start_date.required' => 'Trường ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Trường ngày bắt đầu phải là kiểu dữ liệu ngày.',

            'expire_date.required' => 'Trường ngày kết thúc là bắt buộc.',
            'expire_date.date' => 'Trường ngày kết thúc phải là kiểu dữ liệu ngày.',
            'expire_date.after' => 'Trường ngày kết thúc phải lớn hơn ngày bắt đầu.',

            'max_usage.integer' => 'Trường số lần sử dụng phải là số nguyên.',

            'system_wide.required' => 'Trường áp dụng toàn hệ thống là bắt buộc.',
            'system_wide.boolean' => 'Trường áp dụng toàn hệ thống phải là kiểu boolean.',

            'selected_users.array' => 'Trường danh sách người dùng phải là một mảng.',
            'selected_users.*.exists' => 'Người dùng được chọn không tồn tại trong hệ thống.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'system_wide' => filter_var($this->input('system_wide'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
