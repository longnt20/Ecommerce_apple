<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    public function process(CheckoutRequest $request, \App\Services\Ordering\PlaceOrder $placeOrder, PaymentService $payments)
    {
        $order = $placeOrder->place($request->validated(), $request->user()->id);
        if (strtolower($order->payment_method) === 'cod') {
            return response()->json(['message' => 'Đặt hàng thành công (COD)', 'order_id' => $order->id, 'final_amount' => (int) $order->final_amount]);
        }
        if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
            return response()->json(['order_id' => $order->id, 'payment_url' => null, 'payment_status' => $order->payment_status]);
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
