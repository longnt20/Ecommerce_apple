<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function homeProducts()
    {
        try {
            // Lấy danh sách sản phẩm đang hoạt động + load category + variants
            $products = Product::with(['category', 'variants'])
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->latest()
                ->take(20) // Giới hạn 20 sản phẩm đầu tiên (tuỳ bạn)
                ->get();
            // Trả về dữ liệu giống format bạn yêu cầu
            $data = [
                'items' => $products->flatMap(function ($product) {
                    // Nếu có biến thể thì map từng biến thể
                    if ($product->variants->count() > 0) {
                        return $product->variants->map(function ($variant) use ($product) {
                            $originalPrice = $variant->price ?? 0;
                            $finalPrice = $variant->cost_price ?? $originalPrice;
                            $discountPercent = $originalPrice > 0
                                ? round((($originalPrice - $finalPrice) / $originalPrice) * 100)
                                : 0;

                            return [
                                'id' => $variant->id,
                                'product_id' => $product->id,
                                'slug' => $product->slug,
                                'name' => $product->name . ' - ' . ($variant->storage ?? ''),
                                'category_id' => $product->category_id,
                                'thumbnail' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
                                'original_price' => number_format($originalPrice, 0, ',', '.'),
                                'final_price' => number_format($finalPrice, 0, ',', '.'),
                                'discount_percent' => $discountPercent,
                            ];
                        });
                    }

                    // Nếu không có biến thể thì trả Product gốc
                    $originalPrice = $product->default_price ?? 0;
                    $finalPrice = $originalPrice;
                    $discountPercent = $originalPrice > 0
                        ? round((($originalPrice - $finalPrice) / $originalPrice) * 100)
                        : 0;

                    return [[
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'slug' => $product->slug,
                        'name' => $product->name,
                        'category_id' => $product->category_id,
                        'thumbnail' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
                        'original_price' => number_format($originalPrice, 0, ',', '.'),
                        'final_price' => number_format($finalPrice, 0, ',', '.'),
                        'discount_percent' => $discountPercent,
                    ]];
                }),
            ];
            $items = collect($data['items'])->unique('product_id')->values();
            return response()->json([
                'status' => true,
                'data' => $items
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function productDetail(Request $request, $slug)
    {
        try {
            // Lấy sản phẩm + quan hệ cần thiết
            $product = Product::with(['category', 'variants', 'specs'])
                ->where('slug', $slug)
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->firstOrFail();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
            }
            // Lấy variant_id từ query string
            $variantId = $request->query('variant_id');

            // Tìm variant theo ID nếu có
            $selectedVariant = $product->variants->where('id', $variantId)->first();

            // Nếu không tìm thấy → lấy variant đầu tiên
            if (!$selectedVariant) {
                $selectedVariant = $product->variants->first();
            }
            //Lấy kho hàng còn sản phẩm 
            $warehouses = $product->inventories()
                ->where('available_quantity', '>', 0)
                ->with('warehouse')
                ->get()
                ->groupBy('warehouse_id') // 🔥 Nhóm theo kho, tránh bị lặp
                ->map(function ($items) {
                    $w = $items->first()->warehouse;

                    return [
                        'warehouse_id' => $w->id,
                        'name' => $w->name,
                        'city' => $w->city,
                        'district' => $w->district,
                        'address' => $w->address,
                        'phone' => $w->phone,
                        'stock' => $items->sum('available_quantity'), // 🔥 cộng tồn của tất cả variant
                    ];
                })
                ->values(); // trả về array chuẩn
            // Lấy 5 bài viết liên quan đến danh mục sản phẩm
            $blogs = Post::where('category_id', $product->category_id)
                ->latest()
                ->take(5)
                ->get(['id', 'title', 'thumbnail', 'created_at'])
                ->map(function ($blog) {
                    $blog->thumbnail = asset('storage/' . $blog->thumbnail);
                    return $blog;
                });

            // Format biến thể
            $variants = $product->variants->map(function ($variant) use ($product) {
                $originalPrice = $variant->price ?? 0;
                $finalPrice = $variant->cost_price ?? $originalPrice;
                // 🔥 LẤY TỒN KHO
                $stockQuantity = $variant->inventory->sum('quantity');
                $availableStock = $variant->inventory->sum('available_quantity');
                return [
                    'id' => $variant->id,
                    'storage' => $variant->storage,
                    'color' => $variant->color_label,
                    'thumbnail' => $variant->thumbnail ? asset('storage/' . $variant->thumbnail) : null,
                    'original_price' => number_format($originalPrice, 0, ',', '.'),
                    'final_price' => number_format($finalPrice, 0, ',', '.'),
                    'stock_quantity' => $stockQuantity,
                    'available_quantity' => $availableStock,
                ];
            });

            // Nếu không có biến thể → dùng giá mặc định
            if ($variants->count() == 0) {
                $variants = collect([
                    [
                        'id' => $product->id,
                        'storage' => null,
                        'color' => null,
                        'original_price' => number_format($product->default_price ?? 0, 0, ',', '.'),
                        'final_price' => number_format($product->default_price ?? 0, 0, ',', '.'),
                    ]
                ]);

                $selectedVariant = (object) $variants->first();
            }

            // Dữ liệu trả về
            $data = [
                'id' => $product->id,
                'name' => $product->name,
                'slug'  => $product->slug,
                'gallery' => collect($product->gallery)->map(function ($path) {
                    return asset('storage/' . $path);
                }),
                'warehouse' => $warehouses,
                'warehouse_count' => $warehouses->count(),
                'specs' => $product->specs->map(function ($spec) {
                    return [
                        'id' => $spec->id,
                        'name' => $spec->spec_name,
                        'value' => $spec->spec_value,
                    ];
                }),
                'selected_variant' => [
                    'id' => $selectedVariant->id,
                    'storage' => $selectedVariant->storage,
                    'color' => $selectedVariant->color ?? null,
                    'original_price' => number_format($selectedVariant->price ?? 0, 0, ',', '.'),
                    'final_price' => number_format($selectedVariant->cost_price ?? ($selectedVariant->price ?? 0), 0, ',', '.'),
                ],
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ],
                'thumbnail' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
                'variants' => $variants,
                'blogs'     => $blogs,
                'short_description' => $product->short_description,
                'description' => $product->description,

            ];

            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
