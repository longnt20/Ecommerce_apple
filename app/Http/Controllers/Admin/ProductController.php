<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Modules\Catalog\Infrastructure\SaveProduct;
use App\Traits\GeneratesBarcode;
use App\Traits\LoggableTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use GeneratesBarcode, LoggableTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::withCount('variants')->latest('id')->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            app(SaveProduct::class)->save($request->validated());

            return redirect()->route('admin.products.index')->with('success', 'Thêm sản phẩm thành công');
        } catch (ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $this->logError($error);

            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categories = Category::all();
        $product = Product::with(['specs', 'variants'])->findOrFail($id);

        return view('admin.products.show', compact('product', 'categories'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $categories = Cache::remember('categories_list', 3600, function () {
            return Category::select('id', 'name')->get();
        });

        $product = Product::with([
            'specs:id,product_id,spec_name,spec_value',
            'variants:id,product_id,sku,price,cost_price,color,storage,thumbnail',
        ])->findOrFail($id);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        try {
            app(SaveProduct::class)->save($request->validated(), (int) $id);

            return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công');
        } catch (ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $this->logError($error);

            return back()->with('error', 'Không thể cập nhật sản phẩm, vui lòng thử lại sau');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return redirect()->route('admin.products.index')->with('success', 'Đã chuyển vào thùng rác!');
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể xóa sản phẩm: '.$th->getMessage());
        }
    }

    public function trash()
    {
        $productDeleted = Product::onlyTrashed()->latest('id')->paginate(10);

        return view('admin.products.trash', compact('productDeleted'));
    }

    public function restore(string $id)
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $product->restore();

            return redirect()->route('admin.products.trash')->with('success', 'Khôi phục sản phẩm thành công');
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể khôi phục sản phẩm: '.$th->getMessage());
        }
    }

    public function forceDelete($id)
    {
        return back()->with('warning', 'Chỉ hỗ trợ lưu trữ để bảo toàn lịch sử nghiệp vụ.');
    }
}
