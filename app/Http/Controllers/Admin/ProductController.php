<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::latest('id')->paginate(10);
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
        // try {
            $data = $request->validated();
            // dd($request->all()); 
            // Upload thumbnail
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
            }
            $galleryPaths = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $path = $file->store('products/gallery', 'public'); // lưu vào storage/app/public/products
                    $galleryPaths[] = $path;
                }
            }
            // Upload gallery
            $data['gallery'] = $galleryPaths;

            // Slug
            $data['slug'] = Str::slug($data['name']);
            // dd($data['gallery']);
            $product = Product::create($data);
            if ($request->has('specs')) {
                foreach ($request->specs as $spec) {
                    if (!empty($spec['name']) && !empty($spec['value'])) {
                        $product->specs()->create([
                            'spec_name'  => $spec['name'],
                            'spec_value' => $spec['value'],
                        ]);
                    }
                }
            }
            return redirect()->route('admin.products.index')->with('success', 'Thêm sản phẩm thành công');
        // } 
        // catch (\Throwable $th) {
        //     //throw $th;
        //     return back()->with('error', 'Không thể thêm sản phẩm: ' . $th->getMessage());
        // }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categories = Category::all();
        $product = Product::with('specs')->findOrFail($id);
        return view('admin.products.show', compact('product', 'categories'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $categories = Category::all();
        $product = Product::with('specs')->findOrFail($id);
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        try {
            $product = Product::findOrFail($id);

            $data = $request->validated();

            // Upload image(chỉ khi có ảnh mới)
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
            } else {
                $data['thumbnail'] = $product->thumbnail; // giữ nguyên ảnh
            }

            // Lấy gallery cũ
            $gallery = $product->gallery ?? [];
            if (!is_array($gallery)) {
                $gallery = json_decode($gallery, true) ?? [];
            }

            // Xóa ảnh được tick chọn
            if ($request->filled('remove_gallery')) {
                foreach ($request->remove_gallery as $remove) {
                    // Xóa file khỏi storage
                    Storage::disk('public')->delete($remove);
                    // Xóa khỏi mảng
                    $gallery = array_filter($gallery, fn($item) => $item !== $remove);
                }
            }

            // Thêm ảnh mới
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $path = $file->store('products/gallery', 'public');
                    $gallery[] = $path;
                }
            }
            $data['gallery'] = array_values($gallery);

            $data['slug'] = Str::slug($data['name']);
            // cập nhật product
            $product->update($data);
            // Cập nhật thông số kỹ thuật
            if ($request->has('specs')) {
                $specIds = [];

                foreach ($request->specs as $spec) {
                    // Nếu có id -> update
                    if (!empty($spec['id'])) {
                        $existingSpec = $product->specs()->where('id', $spec['id'])->first();
                        if ($existingSpec) {
                            $existingSpec->update([
                                'spec_name'  => $spec['name'],
                                'spec_value' => $spec['value'],
                            ]);
                            $specIds[] = $existingSpec->id;
                        }
                    } else {
                        // Nếu không có id -> tạo mới
                        if (!empty($spec['name']) && !empty($spec['value'])) {
                            $newSpec = $product->specs()->create([
                                'spec_name'  => $spec['name'],
                                'spec_value' => $spec['value'],
                            ]);
                            $specIds[] = $newSpec->id;
                        }
                    }
                }

                // Xoá các spec cũ mà không có trong request
                $product->specs()->whereNotIn('id', $specIds)->delete();
            } else {
                // Nếu không có spec nào được gửi lên -> xoá hết
                $product->specs()->delete();
            }
            return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công');
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể cập nhật sản phẩm: ' . $th->getMessage());
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
            return back()->with('error', 'Không thể xóa sản phẩm: ' . $th->getMessage());
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
            return back()->with('error', 'Không thể khôi phục sản phẩm: ' . $th->getMessage());
        }
    }
    public function forceDelete(string $id){
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $product->forceDelete();
            return redirect()->route('admin.products.trash')->with('success', 'Xóa cứng sản phẩm thành công');
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể xóa cứng sản phẩm: ' . $th->getMessage());
        }
    }
}
