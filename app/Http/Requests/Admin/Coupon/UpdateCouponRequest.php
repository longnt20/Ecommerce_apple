<?php

namespace App\Http\Requests\Admin\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'code' => ['required','string','max:255',Rule::unique('coupons','code')->ignore($this->route('coupon'))],
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric',
            'discount_max_value' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'expire_date' => 'required|date|after:start_date',
            'max_usage' => 'nullable|integer',
            'status' => 'boolean',
            'system_wide' => 'nullable|boolean',
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
    public function messages()
    {
        return [
            //User
            'user_id.required' => 'Trường user_id là bắt buộc.',
            'user_id.exists' => 'Trường user_id không tồn tại.',

            //Name
            'name.required' => 'Trường name là bắt buộc.',
            'name.string' => 'Trường name phải là chuỗi ký tự.',
            'name.max'=>'Trường name vượt quá 255 kí tự',

            //Code
            'code.required' => 'Trường code là bắt buộc.',
            'code.string' => 'Trường code phải là chuỗi ký tự.',
            'code.max'=>'Trường code vượt quá 255 kí tự',
            'code.unique' => 'Trường code đã tồn tại trong hệ thống.',

            //Discount_type
            'discount_type.required' => 'Trường discount_type là bắt buộc.',
            'discount_type.in' => 'Trường discount_type không hợp lệ.',

            //Discount_value
            'discount_value.required' => 'Trường discount_value là bắt buộc.',
            'discount_value.numeric' => 'Trường discount_value phải là số.',

            //Discount_max_value
            'discount_max_value.numeric' => 'Trường discount_max_value phải là số.',
            'discount_max_value.min' => 'Trường discount_max_value phải lớn hơn 0.',
            
            //Description
            'description.string' => 'Trường description phải là chuỗi ký tự.',

            //Start_date
            'start_date.required'=>'Trường start_date là bắt buộc',
            'start_date.date'=>'Sai kiểu dữ liệu date',

             //Expire_date
             'expire_date.required'=>'Trường expire_date là bắt buộc',
             'expire_date.date'=>'Sai kiểu dữ liệu date',
                'expire_date.after'=>'Trường expire_date phải sau start_date',

             //Used_count
             'used_count.required'=>'Trường used_count là bắt buộc',
             'used_count.integer'=>'Sai kiểu dữ liệu interger',
             'system_wide.boolean' => 'Trường áp dụng toàn hệ thống phải là kiểu boolean.',
 
             'selected_users.array' => 'Trường danh sách người dùng phải là một mảng.',
             'selected_users.*.exists' => 'Người dùng được chọn không tồn tại trong hệ thống.',
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
        if (is_string($this->selected_users)) {
            $selectedUsers = json_decode($this->selected_users, true);
    
            // Chỉ lấy ID thôi
            $userIds = collect($selectedUsers)->pluck('id')->toArray();
    
            $this->merge([
                'selected_users' => $userIds,
            ]);
        }
    }
}
