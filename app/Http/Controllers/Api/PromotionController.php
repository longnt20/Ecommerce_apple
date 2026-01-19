<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $categoryId = $request->query('category_id');
            $promotion = Promotion::with(['category', 'items.item', 'frame'])
                ->where('is_featured', true)
                ->where('status', 1)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->latest()
                ->first();

            if (!$promotion) {
                return response()->json([
                    'data' => null,
                    'message' => 'Không có chương trình khuyến mãi nào đang diễn ra.'
                ], 200);
            }
            return response()->json([
                'data' => [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'thumbnail' => $promotion->thumbnail ? asset('storage/' . $promotion->thumbnail)
                            : null,
                    'slug' => $promotion->slug,
                    'frame' => $promotion->frame ? [
                        'id' => $promotion->frame->id,
                        'name' => $promotion->frame->name,
                        'top_background' => $promotion->frame->top_background
                            ? asset('storage/' . $promotion->frame->top_background)
                            : null,
                        'bottom_background' => $promotion->frame->bottom_background
                            ? asset('storage/' . $promotion->frame->bottom_background)
                            : null,
                        'ribbon_image' => $promotion->frame->ribbon_image
                            ? asset('storage/' . $promotion->frame->ribbon_image)
                            : null,
                        'left_decor_image' => $promotion->frame->left_decor_image
                            ? asset('storage/' . $promotion->frame->left_decor_image)
                            : null,
                        'right_decor_image' => $promotion->frame->right_decor_image
                            ? asset('storage/' . $promotion->frame->right_decor_image)
                            : null,
                        'is_active'=> $promotion->frame->is_active,
                    ] : null,
                    'start_date' => $promotion->start_date,
                    'end_date' => $promotion->end_date,
                    'items' => $promotion->items->map(function ($item) {
                        $model = $item->item;
                        $type = class_basename($model);

                        if ($type === 'Product') {
                            $originalPrice = $model->default_price ?? 0;
                            $finalPrice = $originalPrice;
                        } elseif ($type === 'ProductVariant') {
                            $originalPrice = $model->price ?? 0;
                            $finalPrice = $model->cost_price ?? 0;
                            $discountPercent = $originalPrice > 0
                                ? round((($originalPrice - $finalPrice) / $originalPrice) * 100)
                                : 0;
                        } else {
                            $originalPrice = 0;
                            $finalPrice = 0;
                            $discountPercent = 0;
                        }

                        return [
                            'id' => $model->id,
                            'name' => $type === 'ProductVariant' && $model->product
                                ? $model->product->name . ' - ' . $model->storage
                                : $model->name,
                            'category_id' => $model->product->category->id ?? $model->category->id ?? null,
                            'slug' => $model->product->slug,
                            'thumbnail' => asset('storage/' . $model->thumbnail),
                            'original_price' => number_format($originalPrice, 0, ',', '.'),
                            'final_price' => number_format($finalPrice, 0, ',', '.'),
                            'discount_percent' => $discountPercent ?? 0,
                            'type' => $type,
                        ];
                    }),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        }
    }
    public function promotionCategories()
{
    $promotion = Promotion::with([
        'items.item' => function (MorphTo $morph) {
            $morph->morphWith([
                \App\Models\ProductVariant::class => ['product.category'],
                \App\Models\Product::class => ['category'],
            ]);
        }
    ])
        ->where('is_featured', true)
        ->where('status', 1)
        ->whereDate('start_date', '<=', now())
        ->whereDate('end_date', '>=', now())
        ->latest()
        ->first();

    // ✅ Không có promotion → trả data rỗng
    if (!$promotion) {
        return response()->json([
            'status' => true,
            'data' => []
        ], 200);
    }

    $categories = $promotion->items
        ->map(function ($item) {
            $model = $item->item;

            if (!$model) {
                return null;
            }

            if ($model instanceof \App\Models\ProductVariant) {
                return $model->product?->category;
            }

            if ($model instanceof \App\Models\Product) {
                return $model->category;
            }

            return null;
        })
        ->filter()
        ->unique('id')
        ->values()
        ->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name
        ]);

    return response()->json([
        'status' => true,
        'data' => $categories
    ], 200);
}

}
