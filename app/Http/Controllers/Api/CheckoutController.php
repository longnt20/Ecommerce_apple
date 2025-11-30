<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        $cart = Cart::where('user_id', Auth::id())->firstOrFail();

        DB::beginTransaction();

        try {
            // 1. TẠO ĐƠN HÀNG
            $order = Order::create([
                'user_id'  => Auth::id(),
                'status'   => 'pending',
                'subtotal' => 0,
                'total'    => 0
            ]);

            $subtotal = 0;

            foreach ($cart->items as $item) {

                // 2. Check tồn kho
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->firstOrFail();

                if ($inventory->available_quantity < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Không đủ hàng cho biến thể ' . $item->productVariant->color_label
                    ], 400);
                }

                // 3. Reserve tồn kho
                $inventory->reserve($item->quantity);

                // 4. Ghi InventoryTransaction
                InventoryTransaction::create([
                    'warehouse_id'      => $inventory->warehouse_id,
                    'product_variant_id'=> $item->product_variant_id,
                    'type'              => 'reserve',
                    'quantity'          => $item->quantity,
                    'before_quantity'   => $inventory->quantity,
                    'after_quantity'    => $inventory->quantity, // reserve không làm giảm quantity
                    'reference_type'    => 'order',
                    'reference_id'      => $order->id,
                    'reason'            => 'Reserve stock for order',
                ]);

                // 5. Lưu order_items
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'product_variant_id'=> $item->product_variant_id,
                    'product_name'      => $item->product->name,
                    'variant_name'      => $item->productVariant->color_label,
                    'quantity'          => $item->quantity,
                    'price'             => $item->price_at_add,
                    'total'             => $item->quantity * $item->price_at_add
                ]);

                $subtotal += $item->quantity * $item->price_at_add;
            }

            // 6. Update tổng đơn
            $order->subtotal = $subtotal;
            $order->total = $subtotal;
            $order->save();

            // 7. Xóa giỏ hàng sau khi checkout
            CartItem::where('cart_id', $cart->id)->delete();

            DB::commit();

            return response()->json(['order_id' => $order->id, 'message' => 'Checkout thành công']);
        
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

