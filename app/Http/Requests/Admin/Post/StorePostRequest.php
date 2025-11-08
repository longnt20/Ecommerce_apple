<?php

namespace App\Http\Requests\Admin\Posts;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => ['in:draft,pending,published,private,scheduled'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable'],
        ];
    }

    public function messages()
    {
        return [
            'category_id.required' => 'Danh mục không được để trống.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'title.required' => 'Tên tiêu đề không được để trống.',
            'title.string' => 'Tên tiêu đề phải là một chuỗi.',
            'title.max' => 'Tên tiêu đề không được quá 255 kí tự.',
            'description.string' => 'Mô tả phải là một chuỗi.',
            'content.string' => 'Nội dung phải là một chuỗi.',
            'thumbnail.image' => 'Ảnh đại diện phải là một tập tin hình ảnh.',
            'thumbnail.mimes' => 'Ảnh đại diện phải có định dạng jpeg, png, jpg, gif, webp.',
            'thumbnail.max' => 'Ảnh đại diện không được vượt quá 2MB.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'published_at.date' => 'Ngày xuất bản phải là một ngày hợp lệ.',
            'tags.array' => 'Thẻ phải là một mảng.',
            'tags.*.nullable' => 'Thẻ không hợp lệ.',
        ];
    }
}
