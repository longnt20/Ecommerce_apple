<?php

namespace App\Modules\Payments\Infrastructure;

use App\Models\PaymentAttempt;
use App\Modules\Payments\Contracts\Attempt;
use App\Modules\Payments\Contracts\PaymentCandidate;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Contracts\PaymentLedger;
use App\Modules\Payments\Contracts\PaymentOrder;
use App\Modules\Payments\Contracts\PaymentResult;
use App\Modules\Payments\Domain\PaymentDecision;
use App\SharedKernel\Clock;
use App\SharedKernel\Vnd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentPaymentLedger implements PaymentLedger
{
    public function __construct(private PaymentGateway $gateway, private Clock $clock, private PaymentDecision $decision) {}

    public function assertAvailable(): void
    {
        $this->gateway->assertConfigured();
    }

    private function view(PaymentAttempt $attempt): Attempt
    {
        return new Attempt($attempt->id, $attempt->order_id, $attempt->reference, $attempt->merchant, $attempt->amount_vnd,
            $attempt->currency, $attempt->status, $attempt->created_at->toDateTimeImmutable(), $attempt->expires_at->toDateTimeImmutable());
    }

    public function start(PaymentOrder $order): Attempt
    {
        $this->assertAvailable();

        return $this->view(PaymentAttempt::create(['order_id' => $order->id, 'reference' => (string) Str::ulid(),
            'merchant' => config('vnpay.vnp_tmn_code'), 'amount_vnd' => Vnd::amount($order->amount), 'currency' => 'VND', 'status' => 'pending',
            'expires_at' => $order->holdExpiresAt ?? $this->clock->now()->modify('+15 minutes')]));
    }

    public function url(PaymentOrder $order, string $ip): string
    {
        abort_unless(strtolower($order->method) === 'vnpay' && $order->paymentStatus === 'unpaid' && $order->status !== 'cancelled', 409, 'Đơn không thể tiếp tục thanh toán.');
        $attempt = $this->view(PaymentAttempt::where('order_id', $order->id)->firstOrFail());
        abort_unless($attempt->status === 'pending' && $attempt->expiresAt > $this->clock->now(), 409, 'Lượt thanh toán không còn hiệu lực.');
        abort_unless(Vnd::amount($order->amount) === $attempt->amount, 409, 'Số tiền đơn hàng đã thay đổi.');

        return $this->gateway->paymentUrl($attempt, $ip);
    }

    private function invalidNotification(array $query): ?PaymentResult
    {
        if (! $this->gateway->verified($query)) {
            return new PaymentResult('97');
        }
        foreach (['vnp_TxnRef', 'vnp_TmnCode', 'vnp_Amount', 'vnp_ResponseCode', 'vnp_TransactionStatus', 'vnp_TransactionNo'] as $key) {
            if (! isset($query[$key]) || ! is_string($query[$key]) || $query[$key] === '') {
                return new PaymentResult('99');
            }
        }
        if (! preg_match('/^\d{1,15}$/D', $query['vnp_TransactionNo']) || ! preg_match('/^\d{2}$/D', $query['vnp_ResponseCode'])
            || ! preg_match('/^\d{2}$/D', $query['vnp_TransactionStatus'])) {
            return new PaymentResult('99');
        }

        return null;
    }

    public function candidate(array $query): PaymentCandidate|PaymentResult
    {
        if ($invalid = $this->invalidNotification($query)) {
            return $invalid;
        }
        $attempt = PaymentAttempt::where('reference', $query['vnp_TxnRef'])->first();
        if (! $attempt || $attempt->merchant !== $query['vnp_TmnCode'] || $attempt->merchant !== config('vnpay.vnp_tmn_code')) {
            return new PaymentResult('01');
        }

        return new PaymentCandidate($attempt->id, $attempt->order_id, $query);
    }

    private function lock(PaymentCandidate $candidate): Attempt
    {
        $this->assertTransaction();

        return $this->view(PaymentAttempt::whereKey($candidate->attemptId)->lockForUpdate()->firstOrFail());
    }

    public function settle(PaymentCandidate $candidate, callable $orderSnapshot): PaymentResult
    {
        $this->assertTransaction();
        $query = $candidate->query;
        // A DTO is transport, not proof: enforce authenticity again at the financial write boundary.
        if ($invalid = $this->invalidNotification($query)) {
            return $invalid;
        }
        $attempt = $this->lock($candidate);
        if ($attempt->reference !== ($query['vnp_TxnRef'] ?? null) || $attempt->merchant !== ($query['vnp_TmnCode'] ?? null)
            || $attempt->merchant !== config('vnpay.vnp_tmn_code')) {
            return new PaymentResult('01');
        }
        $order = $orderSnapshot();
        if ($attempt->id !== $candidate->attemptId || $attempt->orderId !== $candidate->orderId || $attempt->orderId !== $order->id || strtolower($order->method) !== 'vnpay') {
            return new PaymentResult('01');
        }
        if ($attempt->currency !== 'VND' || ($query['vnp_CurrCode'] ?? 'VND') !== 'VND'
            || ! preg_match('/^[0-9]{1,12}$/D', $query['vnp_Amount']) || (int) $query['vnp_Amount'] !== $attempt->amount * 100
            || Vnd::amount($order->amount) !== $attempt->amount) {
            return new PaymentResult('04');
        }
        $identity = $query;
        unset($identity['vnp_SecureHash'], $identity['vnp_SecureHashType']);
        ksort($identity);
        $fingerprint = hash('sha256', json_encode($identity));
        if (DB::table('payment_events')->where('fingerprint', $fingerprint)->exists() || $attempt->status !== 'pending' || $order->paymentStatus !== 'unpaid') {
            return new PaymentResult('02');
        }
        $success = $query['vnp_ResponseCode'] === '00' && $query['vnp_TransactionStatus'] === '00';
        $outcome = $this->decision->outcome($success, $attempt, $order, $this->clock->now());
        PaymentAttempt::whereKey($attempt->id)->update(['status' => $outcome, 'provider_transaction' => $success ? $query['vnp_TransactionNo'] : null]);
        DB::table('payment_events')->insert(['payment_attempt_id' => $attempt->id, 'fingerprint' => $fingerprint,
            'response_code' => $query['vnp_ResponseCode'], 'transaction_status' => $query['vnp_TransactionStatus'],
            'outcome' => $outcome, 'created_at' => $this->clock->now(), 'updated_at' => $this->clock->now()]);

        return new PaymentResult('00', $outcome, $success ? $query['vnp_TransactionNo'] : null);
    }

    public function paidAttempt(int $orderId): bool
    {
        $this->assertTransaction();

        return PaymentAttempt::where('order_id', $orderId)->lockForUpdate()->first()?->status === 'paid';
    }

    public function browserOrder(array $query): ?int
    {
        if (! $this->gateway->verified($query) || ! \Illuminate\Support\Facades\Schema::hasTable('payment_attempts')) {
            return null;
        }
        $id = PaymentAttempt::where('reference', $query['vnp_TxnRef'] ?? null)->where('merchant', $query['vnp_TmnCode'] ?? null)->value('order_id');

        return $id === null ? null : (int) $id;
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Payment writes/locks require the Ordering transaction coordinator.');
        }
    }
}
