<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cod,vnpay',
            'items' => 'required|array|min:1',

            'items.*.product_id'   => 'required|integer',
            'items.*.variant_id'   => 'required|integer',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.price'        => 'required|numeric|min:0',
            'fullname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'required|string|max:255',
            'ward' => 'required|string',
            'district' => 'required|string',
            'province' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $paymentMethod = $request->payment_method;
        $items = $request->items;
        $cart = Cart::where('user_id', Auth::id())->first();
        $code = 'DH' . date('Ymd') . '-' . rand(10000, 99999);
        DB::beginTransaction();

        try {
            // ================= 1. TẠO ORDER ====================
            $order = Order::create([
                'code'            => $code,
                'user_id'         => Auth::id(),
                'status'          => 'pending',
                'total_price'     => 0,
                'final_amount'    => 0,
                'payment_method'  => $paymentMethod,
                'payment_status'  => $paymentMethod === 'cod' ? 'unpaid' : 'unpaid',
                'transaction_id'  => null,  // Sẽ cập nhật khi thanh toán online (VNPAY)

                // Customer Info
                'fullname'        => $request->fullname,
                'phone'           => $request->phone,
                'email'           => $request->email,
                'address'         => $request->address,
                'ward'            => $request->ward,
                'district'        => $request->district,
                'province'        => $request->province,
                'note'            => $request->note,
            ]);

            $subtotal = 0;

            foreach ($items as $item) {

                // Check tồn kho
                $inventory = Inventory::where('product_variant_id', $item['variant_id'])
                    ->lockForUpdate()
                    ->first();

                if ($inventory->available_quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Không đủ hàng cho biến thể '
                    ], 400);
                }

                // Reserve tồn kho
                $inventory->reserve($item['quantity']);

                InventoryTransaction::create([
                    'warehouse_id'      => $inventory->warehouse_id,
                    'product_variant_id' => $item['variant_id'],
                    'type'              => 'reserve',
                    'quantity'          => $item['quantity'],
                    'before_quantity'   => $inventory->quantity,
                    'after_quantity'    => $inventory->quantity,
                    'reference_type'    => 'order',
                    'reference_id'      => $order->id,
                    'reason'            => 'Reserve stock for order'
                ]);
                $product = Product::find($item['product_id']);
                $variant = ProductVariant::find($item['variant_id']);
                // Lưu order_item
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item['product_id'],
                    'variant_id'        => $item['variant_id'],
                    'product_name'      => $product ? $product->name : '',
                    'variant_name'      => $variant
                        ? ($variant->storage . '-' . $variant->color_label)
                        : '',
                    'quantity'          => $item['quantity'],
                    'price'             => $item['price'],
                    'total'             => $item['quantity'] * $item['price']
                ]);

                $subtotal += $item['quantity'] * $item['price'];
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
                    'status' => 'pending'
                ]);

                return response()->json([
                    'message' => 'Đặt hàng thành công (COD)',
                    'order_id' => $order->id,
                    'final_amount' => $order->final_amount
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
            "vnp_CreateDate" => date('YmdHis'),
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
            "order_id" => $order->id,
        ]);
    }



    // ====================== VNPay Return ==========================
    public function vnpReturn(Request $request)
    {
        $orderId = $request->vnp_TxnRef;
        $orderAmount = $request->vnp_Amount / 100;
        if ($request->vnp_ResponseCode == "00") {
            Order::where('id', $orderId)->update([
                'payment_status' => 'paid',
                'transaction_id' => $request->vnp_TransactionNo,
            ]);
        }

        return redirect("/payment-success?order_id={$orderId}&amount={$orderAmount}");
    }
}
