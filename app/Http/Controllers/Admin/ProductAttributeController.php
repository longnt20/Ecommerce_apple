<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = ProductAttribute::query()
            ->when($request->type, function ($query, $type) {
                $query->byType($type);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('label', 'like', "%{$search}%")
                      ->orWhere('value', 'like', "%{$search}%");
            })
            ->ordered()
            ->paginate(20);

        $types = ProductAttribute::getTypes();

        return view('admin.product-attributes.index', compact('attributes', 'types'));
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