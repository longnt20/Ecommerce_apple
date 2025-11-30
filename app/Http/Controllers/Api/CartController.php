<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Lấy giỏ của user hiện tại
    protected function getUserCart()
    {
        return Cart::firstOrCreate(['user_id' => auth()->id()]);
    }

    public function getCart()
    {
        $cart = $this->getUserCart()->load(['items.product', 'items.variant']);
        return response()->json([
            'items' => $cart->items,
            'total_price' => $cart->totalPrice(),
            'total_quantity' => $cart->totalQuantity()
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            "product_id" => "required|exists:products,id",
            "product_variant_id" => "nullable|exists:product_variants,id",
            "quantity" => "required|integer|min:1"
        ]);

        $cart = $this->getUserCart();

        // kiểm tra trùng sản phẩm + variant → tăng số lượng
        $item = $cart->items()
            ->where("product_id", $request->product_id)
            ->where("product_variant_id", $request->product_variant_id)
            ->first();

        $price = $request->product_variant_id
            ? ProductVariant::find($request->product_variant_id)->cost_price
            : Product::find($request->product_id)->default_price;

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            $cart->items()->create([
                "product_id" => $request->product_id,
                "product_variant_id" => $request->product_variant_id,
                "quantity" => $request->quantity,
                "price_at_add" => $price
            ]);
        }

        return $this->getCart();
    }

    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            "quantity" => "required|integer|min:1"
        ]);

        $item->update(["quantity" => $request->quantity]);

        return $this->getCart();
    }

    public function remove(CartItem $item)
    {
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


