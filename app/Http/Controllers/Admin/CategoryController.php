<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Services\CategoryService;
use Illuminate\Support\Str;

class CategoryController extends BaseCrudController
{
    protected $storeformRequestClass = StoreCategoryRequest::class;

    protected $updateformRequestClass = UpdateCategoryRequest::class;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
        $this->viewPath = 'admin.categories';
        $this->routeName = 'admin.categories';
    }

    public function create()
    {
        $categories = $this->service->getAll();

        return view('admin.categories.create', compact('categories'));
    }

    public function edit($id)
    {
        $categories = $this->service->getAll();
        $item = $this->service->find($id);

        return view('admin.categories.edit', compact('categories', 'item'));
    }

    public function forceDelete($id)
    {
        // The legacy category FK cascades into products and order history.
        return back()->with('warning', 'Danh mục chỉ được lưu trữ để bảo toàn sản phẩm và lịch sử đơn hàng.');
    }

    protected function beforeStore($request, $data)
    {
        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time().'-'.$file->getClientOriginalName();
            $file->storeAs('categories', $fileName, 'public');
            $data['image'] = 'categories/'.$fileName;
        }

        return $data;
    }

    protected function beforeUpdate($request, $data)
    {
        // dd($data);
        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time().'-'.$file->getClientOriginalName();
            $file->storeAs('categories', $fileName, 'public');
            $data['image'] = 'categories/'.$fileName;
        }

        return $data;
    }
}
