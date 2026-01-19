<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Traits\LoggableTrait;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    use LoggableTrait;

    public function homeMain()
    {
        try {
            $data = Cache::remember('home_main', 300, function () {

                $categories = Category::query()
                    ->select('id', 'name', 'slug', 'parent_id')
                    ->with('parent:id,name')
                    ->where('status', 1)
                    ->oldest('id')
                    ->limit(9)
                    ->get();

                $banners = Banner::query()
                    ->select('id', 'title', 'image', 'order')
                    ->where('status', 1)
                    ->orderBy('order')
                    ->with([
                        'products' => fn($q) =>
                        $q->select('products.id', 'name', 'slug')->limit(1)
                    ])
                    ->get()
                    ->map(fn($b) => [
                        'title' => $b->title,
                        'image' => $b->image ? asset("storage/{$b->image}") : null,
                        'order' => $b->order,
                        'product' => optional($b->products->first(), fn($p) => [
                            'name' => $p->name,
                            'slug' => $p->slug,
                        ]),
                    ]);

                return [
                    'categories' => $categories,
                    'banners' => $banners,
                ];
            });

            return response()->json([
                'status' => true,
                ...$data
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function homeProducts()
    {
        try {
            $data = Cache::remember('home_products', 60, function () {

                /* ================= BLOGS ================= */
                $blogs = Post::query()
                    ->where('status', 'published')
                    ->where('is_hot', 1)
                    ->latest()
                    ->limit(5)
                    ->get(['title', 'slug', 'thumbnail'])
                    ->map(fn($b) => [
                        'title' => $b->title,
                        'slug' => $b->slug,
                        'thumbnail' => $b->thumbnail
                            ? asset('storage/' . $b->thumbnail)
                            : null,
                    ]);

                /* ================= PRODUCTS ================= */
                $products = Product::query()
                    ->with([
                        'category:id,name',
                        'variants:id,product_id,price,cost_price,storage'
                    ])
                    ->where('status', 'published')
                    ->where('visibility', 'public')
                    ->latest()
                    ->limit(20)
                    ->get();

                $items = $products->map(function ($product) {
                    if ($product->variants->isNotEmpty()) {
                        $v = $product->variants->first();
                        return [
                            'id' => $v->id,
                            'product_id' => $product->id,
                            'name' => $product->name . ' ' . $v->storage,
                            'slug' => $product->slug,
                            'category_id' => $product->category_id,
                            'thumbnail' => $product->thumbnail
                                ? asset('storage/' . $product->thumbnail)
                                : null,
                            'original_price' => (int) $v->price,
                            'final_price' => (int) $v->cost_price,
                            'discount_percent' => $v->price > 0
                                ? round((($v->price - $v->cost_price) / $v->price) * 100)
                                : 0,
                        ];
                    }

                    return [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'category_id' => $product->category_id,
                        'thumbnail' => $product->thumbnail
                            ? asset('storage/' . $product->thumbnail)
                            : null,
                        'original_price' => (int) $product->default_price,
                        'final_price' => (int) $product->default_price,
                        'discount_percent' => 0,
                    ];
                });

                /* ================= PROMOTION ================= */
                $promotion = Promotion::query()
                    ->with([
                        'frame',
                        'items.item' => function ($morph) {
                            $morph->morphWith([
                                \App\Models\Product::class => ['category'],
                                \App\Models\ProductVariant::class => ['product.category'],
                            ]);
                        }
                    ])
                    ->where('is_featured', true)
                    ->where('status', 1)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->latest()
                    ->first();
                $promotionData = $promotion ? [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'slug' => $promotion->slug,
                    'thumbnail' => $promotion->thumbnail
                        ? asset('storage/' . $promotion->thumbnail)
                        : null,
                    'start_date' => $promotion->start_date,
                    'end_date' => $promotion->end_date,
                    'frame_id' => $promotion->frame_id,
                    'frame' => $promotion->frame ? [
                        'top' => asset('storage/' . $promotion->frame->top_background),
                        'bottom' => asset('storage/' . $promotion->frame->bottom_background),
                        'ribbon' => asset('storage/' . $promotion->frame->ribbon_image),
                        'left' => asset('storage/' . $promotion->frame->left_decor_image),
                        'right' => asset('storage/' . $promotion->frame->right_decor_image),
                    ] : null,

                    'items' => $promotion->items->map(function ($item) {
                        $model = $item->item;

                        if (!$model) return null;

                        if ($model instanceof \App\Models\ProductVariant) {
                            return [
                                'id' => $model->id,
                                'name' => $model->product->name . ' ' . $model->storage,
                                'slug' => $model->product->slug,
                                'category_id' => $model->product->category_id,
                                'thumbnail' => asset('storage/' . $model->product->thumbnail),
                                'final_price' => (int) $model->cost_price,
                                'original_price' => (int) $model->price,
                                'discount_percent' => $model->price > 0
                                    ? round((($model->price - $model->cost_price) / $model->price) * 100)
                                    : 0,
                            ];
                        }

                        return [
                            'id' => $model->id,
                            'name' => $model->name,
                            'slug' => $model->slug,
                            'category_id' => $model->category_id,
                            'thumbnail' => asset('storage/' . $model->thumbnail),
                            'final_price' => (int) $model->default_price,
                        ];
                    }),
                ] : null;
                $promotionCategories = $promotion
                    ? $promotion->items
                    ->map(function ($promotionItem) {
                        $model = $promotionItem->item;

                        if (!$model) return null;

                        if ($model instanceof ProductVariant) {
                            return $model->product?->category;
                        }

                        if ($model instanceof Product) {
                            return $model->category;
                        }

                        return null;
                    })
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])
                    : [];

                return [
                    'products' => $items,
                    'blogs' => $blogs,
                    'promotion' => $promotionData,
                    'promotion_categories' =>$promotionCategories
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Server error'
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
                ->first();

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
