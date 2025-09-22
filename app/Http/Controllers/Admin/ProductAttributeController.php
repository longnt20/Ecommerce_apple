<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index(Request $request)
{
    // Lấy thuộc tính màu sắc
    $colorQuery = ProductAttribute::where('type', 'color');
    if ($request->has('search_color')) {
        $colorQuery->where(function($q) use ($request) {
            $q->where('value', 'like', '%' . $request->search_color . '%')
              ->orWhere('label', 'like', '%' . $request->search_color . '%');
        });
    }
    $colorAttributes = $colorQuery->orderBy('sort_order')->paginate(10, ['*'], 'color_page');

    // Lấy thuộc tính dung lượng
    $storageQuery = ProductAttribute::where('type', 'storage');
    if ($request->has('search_storage')) {
        $storageQuery->where(function($q) use ($request) {
            $q->where('value', 'like', '%' . $request->search_storage . '%')
              ->orWhere('label', 'like', '%' . $request->search_storage . '%');
        });
    }
    $storageAttributes = $storageQuery->orderBy('sort_order')->paginate(10, ['*'], 'storage_page');

    $types = [
        'color' => 'Màu sắc',
        'storage' => 'Dung lượng'
    ];

    return view('admin.product-attributes.index', compact('colorAttributes', 'storageAttributes', 'types'));
}

    public function create()
    {
        $types = ProductAttribute::getTypes();
        return view('admin.product-attributes.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(ProductAttribute::getTypes())),
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'hex_code' => 'required|string|max:7',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        ProductAttribute::create($validated);

        return redirect()->route('admin.product-attributes.index')
            ->with('success', 'Thuộc tính đã được tạo thành công!');
    }

    public function edit(ProductAttribute $productAttribute)
    {
        $types = ProductAttribute::getTypes();
        return view('admin.product-attributes.edit', compact('productAttribute', 'types'));
    }

    public function update(Request $request, ProductAttribute $productAttribute)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(ProductAttribute::getTypes())),
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $productAttribute->update($validated);

        return redirect()->route('admin.product-attributes.index')
            ->with('success', 'Thuộc tính đã được cập nhật!');
    }

    public function destroy(ProductAttribute $productAttribute)
    {
        $productAttribute->delete();

        return redirect()->route('admin.product-attributes.index')
            ->with('success', 'Thuộc tính đã được xóa!');
    }
}