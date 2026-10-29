<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Pricing\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CartController extends Controller
{
    // Lấy giỏ của user hiện tại
    protected function getUserCart()
    {
        if (! auth()->check()) {
            return null;
        }

        return Cart::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }

    public function getCart()
    {
        if (! auth()->check()) {
            return response()->json([
                'items' => [],
                'total_price' => 0,
                'total_quantity' => 0,
                'message' => 'User not authenticated',
            ], 401);
        }

        $cart = $this->getUserCart()->load(['items.product', 'items.variant']);
        foreach ($cart->items as $item) {
            $item->variant?->makeHidden('cost_price');
        }

        return response()->json([
            'items' => $cart->items,
            'total_price' => $cart->totalPrice(),
            'total_quantity' => $cart->totalQuantity(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $quote = app(QuoteService::class)->sku((int) $request->product_id, $request->variant_id ? (int) $request->variant_id : null);
        $cart = $this->getUserCart();

        // kiểm tra trùng sản phẩm + variant → tăng số lượng
        $item = $cart->items()
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $request->variant_id)
            ->first();

        $price = $quote['price'];

        if ($item) {
            $item->quantity += $request->quantity;
            $item->price_at_add = $price;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $request->product_id,
                'product_variant_id' => $request->variant_id,
                'quantity' => $request->quantity,
                'price_at_add' => $price,
            ]);
        }

        return $this->getCart();
    }

    public function update(Request $request, CartItem $item)
    {
        Gate::authorize('update', $item);
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item->update(['quantity' => $request->quantity]);

        return $this->getCart();
    }

    public function remove(CartItem $item)
    {
        Gate::authorize('delete', $item);
        $item->delete();

        return $this->getCart();
    }

    public function clear()
    {
        $cart = $this->getUserCart();
        $cart->items()->delete();

        return $this->getCart();
    }
}
