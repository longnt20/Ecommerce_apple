<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Variant\StoreProductVariantRequest;
use App\Http\Requests\Admin\Variant\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\GeneratesBarcode;

class ProductVariantController extends Controller
{
    use GeneratesBarcode;

    public function index()
    {
        $productVariants = ProductVariant::latest('id')->paginate(10);

        return view('admin.product_variants.index', compact('productVariants'));
    }

    public function create()
    {
        $products = Product::all();

        return view('admin.product_variants.create', compact('products'));
    }

    public function store(StoreProductVariantRequest $request)
    {
        try {
            app(\App\Modules\Catalog\Infrastructure\SaveProduct::class)->variant($request->validated());

            return redirect()->route('admin.product_variants.index')->with('success', 'Thêm biến thể thành công');
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể thêm biến thể, vui lòng thử lại sau');
        }
    }

    public function show($id)
    {
        $productVariant = ProductVariant::with('product')->findOrFail($id);

        return view('admin.product_variants.show', compact('productVariant'));
    }

    public function edit($id)
    {
        $productVariant = ProductVariant::with('product')->findOrFail($id);

        return view('admin.product_variants.edit', compact('productVariant'));
    }

    public function update(UpdateProductVariantRequest $request, $id)
    {
        try {
            app(\App\Modules\Catalog\Infrastructure\SaveProduct::class)->variant($request->validated(), (int) $id);

            return redirect()->route('admin.product_variants.index')->with('success', 'Cập nhật biến thể thành công');
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể cập nhật biến thể, vui lòng thử lại sau');
        }
    }

    public function destroy(string $id)
    {
        try {
            $productVariant = ProductVariant::findOrFail($id);
            $productVariant->delete();

            return redirect()->route('admin.product_variants.index')->with('success', 'Xóa thành công, đã thêm vào thùng rác');
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể xóa sản phẩm'.$th->getMessage());
        }
    }

    public function trash()
    {
        $productVariantDeleted = productVariant::onlyTrashed()->latest('id')->paginate(10);

        return view('admin.product_variants.trash', compact('productVariantDeleted'));
    }

    public function restore(string $id)
    {
        try {
            $productVariant = ProductVariant::onlyTrashed()->findOrFail($id);
            $productVariant->restore();

            return redirect()->route('admin.product_variants.trash')->with('success', 'Khôi phục biến thể thành công');
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể khôi phục sản phẩm'.$th->getMessage());
        }
    }

    public function forceDelete($id)
    {
        return back()->with('warning', 'Chỉ hỗ trợ lưu trữ để bảo toàn lịch sử nghiệp vụ.');
    }
}
