<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Modules\Ordering\Contracts\Checkout;
use App\Modules\Ordering\Contracts\PaymentWorkflow;
use App\Modules\Payments\Contracts\PaymentLedger;
use App\SharedKernel\ActorContext;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function process(CheckoutRequest $request, Checkout $placeOrder, PaymentLedger $payments)
    {
        $order = $placeOrder->place($request->validated(), new ActorContext($request->user()->id));
        if (strtolower($order->paymentMethod) === 'cod') {
            return response()->json(['message' => 'Đặt hàng thành công (COD)', 'order_id' => $order->id, 'final_amount' => (int) $order->amount]);
        }
        if ($order->paymentStatus === 'paid' || $order->status === 'cancelled') {
            return response()->json(['order_id' => $order->id, 'payment_url' => null, 'payment_status' => $order->paymentStatus]);
        }

        return response()->json(['payment_url' => $payments->url($order->payment(), $request->ip()), 'order_id' => $order->id]);
    }

    public function retry(Request $request, int $order, PaymentWorkflow $payments)
    {
        $owned = Order::where('user_id', $request->user()->id)->findOrFail($order);

        return response()->json(['payment_url' => $payments->url($owned->id, $request->ip()), 'order_id' => $owned->id]);
    }

    public function ipn(Request $request, PaymentWorkflow $payments)
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

    public function vnpReturn(Request $request, PaymentLedger $payments)
    {
        // Even a signed browser return never writes payment state.
        $orderId = $this->ambiguousQuery($request) ? null : $payments->browserOrder($request->query());
        $query = $orderId ? ['order_id' => $orderId] : ['payment' => 'unverified'];

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
