<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AIIntentService;
use App\Models\Product;
use OpenAI\Exceptions\RateLimitException;

class ChatAIController extends Controller
{
    public function chat(Request $request, AIIntentService $ai)
    {
        try {
            $request->validate([
                'message' => 'required|string'
            ]);

            // 1️⃣ Phân tích intent
            $intentData = $ai->analyze($request->message);

            // 2️⃣ Nếu KHÔNG phải hỏi sản phẩm
            if (($intentData['intent'] ?? 'chat') === 'chat') {
                return response()->json([
                    'type' => 'text',
                    'reply' => $intentData['reply'] ?? 'Mình sẵn sàng hỗ trợ bạn 😊'
                ]);
            }

            // 3️⃣ Query sản phẩm (AN TOÀN)
            $query = Product::query()->where('status', 1);

            if (!empty($intentData['keywords'])) {
                $query->where('name', 'like', '%' . $intentData['keywords'] . '%');
            }

            if (!empty($intentData['filters']['price_max'])) {
                $query->where('price', '<=', $intentData['filters']['price_max']);
            }

            if (!empty($intentData['filters']['brand'])) {
                $query->where('brand', $intentData['filters']['brand']);
            }

            $products = $query
                ->limit(4)
                ->get([
                    'id',
                    'slug',
                    'name',
                    'price',
                    'thumbnail'
                ]);

            // 4️⃣ Không có sản phẩm
            if ($products->isEmpty()) {
                return response()->json([
                    'type' => 'text',
                    'reply' => 'Hiện mình chưa tìm thấy sản phẩm phù hợp 😢 Bạn cho mình biết thêm nhu cầu để mình gợi ý nhé.'
                ]);
            }

            // 5️⃣ Trả về cho Vue render
            return response()->json([
                'type' => 'product',
                'reply' => $intentData['reply'] ?? 'Mình tìm được vài sản phẩm phù hợp cho bạn 👇',
                'products' => $products
            ]);
        } catch (RateLimitException $e) {
            return [
                'reply' => 'Hệ thống đang bận, bạn thử lại sau nhé ⏳'
            ];
        }
    }
}
