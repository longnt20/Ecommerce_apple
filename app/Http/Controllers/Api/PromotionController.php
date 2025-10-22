<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function index()
    {
        try {
            $promotion = Promotion::with(['category', 'items.item'])
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
                    'thumbnail' => $promotion->thumbnail,
                    'slug' => $promotion->slug,
                    'category' => $promotion->category->name ?? null,
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
                                ? $model->product->name . ' - ' . $model->name
                                : $model->name,
                            'thumbnail' => asset('storage/' . $model->thumbnail),
                            'original_price' => number_format($originalPrice, 0, ',', '.'),
                            'final_price' => number_format($finalPrice, 0, ',', '.'),
                            'discount_percent' => $discountPercent,
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
}
