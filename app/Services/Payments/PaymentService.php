<?php

namespace App\Services\Payments;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\Pricing\Vnd;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private PaymentGateway $gateway) {}

    public function start(Order $order): PaymentAttempt
    {
        $this->gateway->assertConfigured();

        $deadline = Schema::hasTable('inventory_reservations') ? InventoryReservation::where('order_id', $order->id)->first()?->expires_at : null;

        return PaymentAttempt::create([
            'order_id' => $order->id, 'reference' => (string) Str::ulid(),
            'merchant' => config('vnpay.vnp_tmn_code'),
            'amount_vnd' => Vnd::amount((string) $order->final_amount),
            'currency' => 'VND', 'expires_at' => $deadline ?? now()->addMinutes(15),
        ]);
    }

    public function url(Order $order, string $ip): string
    {
        abort_unless(strtolower($order->payment_method) === 'vnpay' && $order->payment_status === 'unpaid'
            && $order->status !== 'cancelled', 409, 'Đơn không thể tiếp tục thanh toán.');
        $attempt = PaymentAttempt::where('order_id', $order->id)->firstOrFail();
        abort_unless($attempt->status === 'pending' && $attempt->expires_at->isFuture(), 409, 'Lượt thanh toán không còn hiệu lực.');
        abort_unless(Vnd::amount((string) $order->final_amount) === $attempt->amount_vnd, 409, 'Số tiền đơn hàng đã thay đổi.');

        return $this->gateway->paymentUrl($attempt, $ip);
    }

    public function notify(array $query): array
    {
        if (! $this->gateway->verified($query)) {
            return $this->ack('97');
        }
        foreach (['vnp_TxnRef', 'vnp_TmnCode', 'vnp_Amount', 'vnp_ResponseCode', 'vnp_TransactionStatus', 'vnp_TransactionNo'] as $key) {
            if (! isset($query[$key]) || ! is_string($query[$key]) || $query[$key] === '') {
                return $this->ack('99');
            }
        }
        if (! preg_match('/^\d{1,15}$/D', $query['vnp_TransactionNo'])
            || ! preg_match('/^\d{2}$/D', $query['vnp_ResponseCode'])
            || ! preg_match('/^\d{2}$/D', $query['vnp_TransactionStatus'])) {
            return $this->ack('99');
        }
        $candidate = PaymentAttempt::where('reference', $query['vnp_TxnRef'])->first();
        if (! $candidate || $candidate->merchant !== $query['vnp_TmnCode']
            || $candidate->merchant !== config('vnpay.vnp_tmn_code')) {
            return $this->ack('01');
        }
        try {
            return DB::transaction(function () use ($candidate, $query) {
                // Order -> attempt: keep the same lock order for retries and callbacks.
                $order = Order::whereKey($candidate->order_id)->lockForUpdate()->firstOrFail();
                $attempt = PaymentAttempt::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                if (strtolower($order->payment_method) !== 'vnpay') {
                    return $this->ack('01');
                }
                if ($attempt->currency !== 'VND' || ($query['vnp_CurrCode'] ?? 'VND') !== 'VND'
                    || ! preg_match('/^[0-9]{1,12}$/D', $query['vnp_Amount'])
                    || (int) $query['vnp_Amount'] !== $attempt->amount_vnd * 100
                    || Vnd::amount((string) $order->final_amount) !== $attempt->amount_vnd) {
                    return $this->ack('04');
                }
                $identity = $query;
                unset($identity['vnp_SecureHash'], $identity['vnp_SecureHashType']);
                ksort($identity);
                $fingerprint = hash('sha256', json_encode($identity));
                if (DB::table('payment_events')->where('fingerprint', $fingerprint)->exists()) {
                    return $this->ack('02');
                }
                if ($attempt->status !== 'pending' || $order->payment_status !== 'unpaid') {
                    return $this->ack('02');
                }
                $success = $query['vnp_ResponseCode'] === '00' && $query['vnp_TransactionStatus'] === '00';
                $outcome = $success ? 'paid' : 'failed';
                $reservation = Schema::hasTable('inventory_reservations')
                    ? InventoryReservation::where('order_id', $order->id)->lockForUpdate()->first() : null;
                // No stock effects or automatic acceptance of late/cancelled payments.
                if ($success && ($order->status === 'cancelled' || $attempt->expires_at->isPast()
                    || ($reservation && (! in_array($reservation->status, ['active', 'committed'], true) || $reservation->expires_at?->isPast())))) {
                    $outcome = 'reconciliation_required';
                }
                $attempt->update(['status' => $outcome, 'provider_transaction' => $success ? $query['vnp_TransactionNo'] : null]);
                DB::table('payment_events')->insert([
                    'payment_attempt_id' => $attempt->id, 'fingerprint' => $fingerprint,
                    'response_code' => $query['vnp_ResponseCode'], 'transaction_status' => $query['vnp_TransactionStatus'],
                    'outcome' => $outcome, 'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($outcome === 'paid') {
                    $order->update(['payment_status' => 'paid', 'transaction_id' => $query['vnp_TransactionNo'], 'paid_at' => now()]);
                    $reservation?->update(['expires_at' => null]);
                }

                return $this->ack('00');
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return $this->ack('99');
        }
    }

    private function ack(string $code): array
    {
        return ['RspCode' => $code, 'Message' => match ($code) {
            '00' => 'Confirm Success', '02' => 'Order already confirmed', '01' => 'Order not found',
            '04' => 'Invalid amount', '97' => 'Invalid signature', default => 'Unable to confirm',
        }];
    }
}
