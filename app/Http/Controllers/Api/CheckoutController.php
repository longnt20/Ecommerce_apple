<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $request->validate([
            'payment_method' => 'required|in:cod,vnpay'
        ]);

        $paymentMethod = $request->payment_method;

        $cart = Cart::where('user_id', Auth::id())->firstOrFail();
        $code = 'DH' . date('Ymd') . '-' . rand(10000, 99999);
        DB::beginTransaction();

        try {
            // ================= 1. TẠO ORDER ====================
            $order = Order::create([
                'code'            =>$code,
                'user_id'         => Auth::id(),
                'status'          => 'pending',
                'total_price'     => 0,
                'final_amount'    => 0,
                'payment_method'  => $paymentMethod,
                'payment_status'  => $paymentMethod === 'cod' ? 'unpaid' : 'unpaid'
            ]);

            $subtotal = 0;

            foreach ($cart->items as $item) {

                // Check tồn kho
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inventory->available_quantity < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Không đủ hàng cho biến thể ' . $item->productVariant->color_label
                    ], 400);
                }

                // Reserve tồn kho
                $inventory->reserve($item->quantity);

                InventoryTransaction::create([
                    'warehouse_id'      => $inventory->warehouse_id,
                    'product_variant_id'=> $item->variant->id,
                    'type'              => 'reserve',
                    'quantity'          => $item->quantity,
                    'before_quantity'   => $inventory->quantity,
                    'after_quantity'    => $inventory->quantity,
                    'reference_type'    => 'order',
                    'reference_id'      => $order->id,
                    'reason'            => 'Reserve stock for order'
                ]);

                // Lưu order_item
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'product_variant_id'=> $item->variant->id,
                    'product_name'      => $item->product->name,
                    'variant_name'      => $item->variant->storage .'-'.$item->variant->color_label,
                    'quantity'          => $item->quantity,
                    'price'             => $item->price_at_add,
                    'total'             => $item->quantity * $item->price_at_add
                ]);

                $subtotal += $item->quantity * $item->price_at_add;
            }

            // Update tổng đơn hàng
            $order->update([
                'total_price' => $subtotal,
                'final_amount'    => $subtotal
            ]);

            // Xóa giỏ hàng
            CartItem::where('cart_id', $cart->id)->delete();

            DB::commit();

            // ================= 2. Nếu COD ====================
            if ($paymentMethod === 'cod') {
                $order->update([
                    'payment_status' => 'unpaid',
                    'status' => 'confirmed'
                ]);

                return response()->json([
                    'message' => 'Đặt hàng thành công (COD)',
                    'order_id' => $order->id,
                    'final_amount'=>$order->final_amount
                ]);
            }

            // ================= 3. Nếu VNPAY ====================
            return $this->createVnPayUrl($order, $request);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    // ====================== Tạo URL thanh toán VNPay ==========================
    private function createVnPayUrl($order, Request $request)
    {
        $vnpUrl       = config("vnpay.vnp_url");
        $returnUrl    = config("vnpay.vnp_return_url");
        $tmnCode      = config("vnpay.vnp_tmn_code");
        $hashSecret   = config("vnpay.vnp_hash_secret");

        $txnRef       = $order->id;
        $orderInfo    = "Thanh toán đơn hàng #{$order->id}";
        $amount       = $order->final_amount * 100;
        $locale       = 'vn';

        $inputData = [
            "vnp_Version"   => "2.1.0",
            "vnp_TmnCode"   => $tmnCode,
            "vnp_Amount"    => $amount,
            "vnp_Command"   => "pay",
            "vnp_CreateDate"=> date('YmdHis'),
            "vnp_CurrCode"  => "VND",
            "vnp_IpAddr"    => $request->ip(),
            "vnp_Locale"    => $locale,
            "vnp_OrderInfo" => mb_convert_encoding($orderInfo, 'UTF-8'),
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $returnUrl,
            "vnp_TxnRef"    => $txnRef
        ];

        ksort($inputData);

        $hashData = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);

        $paymentUrl = $vnpUrl . "?" . http_build_query($inputData) . "&vnp_SecureHash=" . $secureHash;

        return response()->json([
            "payment_url" => $paymentUrl,
            "order_id" => $order->id
        ]);
    }



    // ====================== VNPay Return ==========================
    public function vnpReturn(Request $request)
    {
        $orderId = $request->vnp_TxnRef;
        $orderAmount = $request->vnp_Amount/100;
        if ($request->vnp_ResponseCode == "00") {
            Order::where('id', $orderId)->update([
                'payment_status' => 'paid',
                'status' => 'confirmed'
            ]);
        }

       return redirect("/payment-success?order_id={$orderId}&amount={$orderAmount}");

    }
}
