<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use App\Services\Pricing\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function process(CheckoutRequest $request, QuoteService $pricing, PaymentService $payments, PaymentGateway $gateway)
    {
        if ($request->payment_method === 'vnpay') {
            $gateway->assertConfigured();
        }
        $order = DB::transaction(function () use ($request, $pricing, $payments) {
            $quote = $pricing->quote($request->validated('items'));
            $order = Order::create(array_merge($request->safe()->only([
                'fullname', 'phone', 'email', 'address', 'ward', 'district', 'province', 'note', 'payment_method',
            ]), [
                'code' => 'DH'.date('Ymd').'-'.Str::ulid(), 'user_id' => $request->user()->id,
                'status' => 'pending', 'payment_status' => 'unpaid',
                'total_price' => $quote['total'], 'final_amount' => $quote['total'],
            ]));
            $cart = $request->input('mode', 'cart') === 'cart'
                ? Cart::where('user_id', $request->user()->id)->first() : null;
            foreach ($quote['lines'] as $line) {
                $variant = $line['variant'];
                // Allocation protocol remains in P4; existing selection retained here.
                $inventory = Inventory::where('product_variant_id', $variant->id)->lockForUpdate()->first();
                abort_if(! $inventory || $inventory->available_quantity < $line['quantity'], 400, 'Không đủ hàng cho biến thể.');
                $inventory->reserve($line['quantity']);
                InventoryTransaction::create([
                    'warehouse_id' => $inventory->warehouse_id, 'product_variant_id' => $variant->id,
                    'type' => 'reserve', 'quantity' => $line['quantity'],
                    'before_quantity' => $inventory->quantity, 'after_quantity' => $inventory->quantity,
                    'reference_type' => 'order', 'reference_id' => $order->id, 'reason' => 'Reserve stock for order',
                ]);
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $line['product']->id, 'variant_id' => $variant->id,
                    'product_name' => $line['product']->name, 'variant_name' => $variant->storage.'-'.$variant->color_label,
                    'quantity' => $line['quantity'], 'price' => $line['price'], 'total' => $line['total'],
                ]);
                if ($cart) {
                    $item = $cart->items()->where('product_id', $line['product']->id)
                        ->where('product_variant_id', $variant->id)->lockForUpdate()->first();
                    if ($item) {
                        $item->quantity <= $line['quantity'] ? $item->delete()
                            : $item->update(['quantity' => $item->quantity - $line['quantity']]);
                    }
                }
            }
            if ($request->payment_method === 'vnpay') {
                $payments->start($order);
            }

            return $order;
        });
        if ($request->payment_method === 'cod') {
            return response()->json([
                'message' => 'Đặt hàng thành công (COD)', 'order_id' => $order->id, 'final_amount' => $order->final_amount,
            ]);
        }

        return response()->json(['payment_url' => $payments->url($order, $request->ip()), 'order_id' => $order->id]);
    }

    public function retry(Request $request, int $order, PaymentService $payments)
    {
        $owned = Order::where('user_id', $request->user()->id)->findOrFail($order);

        return response()->json(['payment_url' => $payments->url($owned, $request->ip()), 'order_id' => $owned->id]);
    }

    public function ipn(Request $request, PaymentService $payments)
    {
        if ($this->ambiguousQuery($request)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }
        try {
            return response()->json($payments->notify($request->query()));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['RspCode' => '99', 'Message' => 'Unable to confirm']);
        }
    }

    public function vnpReturn(Request $request, PaymentGateway $gateway)
    {
        // Even a signed browser return never writes payment state.
        $attempt = null;
        if (! $this->ambiguousQuery($request) && $gateway->verified($request->query()) && Schema::hasTable('payment_attempts')) {
            $attempt = PaymentAttempt::where('reference', $request->query('vnp_TxnRef'))
                ->where('merchant', $request->query('vnp_TmnCode'))->first();
        }
        $query = $attempt ? ['order_id' => $attempt->order_id] : ['payment' => 'unverified'];

        return redirect(config('vnpay.vnp_frontend_return_url').'?'.http_build_query($query))
            ->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    private function ambiguousQuery(Request $request): bool
    {
        $seen = [];
        foreach (explode('&', $request->server('QUERY_STRING', '')) as $pair) {
            $key = urldecode(explode('=', $pair, 2)[0]);
            if (str_starts_with($key, 'vnp_')) {
                if (isset($seen[$key]) || ! preg_match('/^vnp_[a-zA-Z0-9]+$/D', $key)) {
                    return true;
                }
                $seen[$key] = true;
            }
        }

        return false;
    }
}
