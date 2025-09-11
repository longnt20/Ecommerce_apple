<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Variant\StoreProductVariantRequest;
use App\Http\Requests\Admin\Variant\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
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
            $data = $request->validated();
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('variants', 'public');
            }
            if (empty($data['barcode'])) {
                $data['barcode'] = $this->generateBarcode();
            }

            $ProductVariant = ProductVariant::create($data);
            return redirect()->route('admin.product_variants.index')->with('success', 'Thêm biến thể thành công');
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể thêm sản phẩm: ' . $th->getMessage());
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
            $productVariant = ProductVariant::with('product')->findOrFail($id);
            $data = $request->validated();
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('variants', 'public');
            }
            $productVariant->update($data);
            return redirect()->route('admin.product_variants.index')->with('success', 'Cập nhật biến thể thành công');
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể cập nhật sản phẩm' . $th->getMessage());
        }
    }
    public function destroy(string $id)
    {
        try {
            $productVariant = ProductVariant::findOrFail($id);
            $productVariant->delete();
            return redirect()->route('admin.product_variants.index')->with('success', 'Xóa thành công, đã thêm vào thùng rác');
        } catch (\Throwable $th) {
            return back()->with('error','Không thể xóa sản phẩm' .$th->getMessage());
        }
    }
    public function trash(){
        $productVariantDeleted = productVariant::onlyTrashed()->latest('id')->paginate(10);
        return view('admin.product_variants.trash', compact('productVariantDeleted'));
    }
    public function restore(string $id){
        try {
            $productVariant = ProductVariant::onlyTrashed()->findOrFail($id);
            $productVariant->restore();
            return redirect()->route('admin.product_variants.trash')->with('success','Khôi phục biến thể thành công');
        } catch (\Throwable $th) {
            return back()->with('error','Không thể khôi phục sản phẩm' . $th->getMessage());
        }
    }
    public function forceDelete(string $id){
        try {
            $productVariant = ProductVariant::onlyTrashed()->findOrFail($id);
            $productVariant->forceDelete();
            return redirect()->route('admin.product_variants.trash')->with('success','Xóa cứng biến thể thành công');
        } catch (\Throwable $th) {
            return back()->with('error','Không thể xóa sản phẩm' . $th->getMessage());
        }
    }
    private function generateBarcode()
    {
        // Sinh barcode ngẫu nhiên 12 số, thêm checksum để thành EAN13
        $code = str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
        return $code;
    }
}
