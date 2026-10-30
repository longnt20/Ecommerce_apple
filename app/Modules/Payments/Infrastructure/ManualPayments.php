<?php

namespace App\Modules\Payments\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Modules\Payments\Contracts\ManualPayments as Contract;
use App\Modules\Payments\Contracts\PaymentOrder;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use App\SharedKernel\Vnd;
use Illuminate\Support\Facades\DB;

/** Facts entered by an administrator after offline reconciliation; no provider call. */
final class ManualPayments implements Contract
{
    public function __construct(private Clock $clock) {}

    public function record(PaymentOrder $order, ActorContext $actor, string $key, array $input): array
    {
        if (DB::transactionLevel() < 1 || $actor->id === null) {
            throw new \LogicException('Manual payments require a locked order transaction and explicit actor.');
        }
        $hash = hash('sha256', $actor->id.'|'.$key);
        $payload = hash('sha256', json_encode([$order->id, $input], JSON_THROW_ON_ERROR));
        $previous = DB::table('payment_adjustment_events')->where('operation_hash', $hash)->first();
        if ($previous) {
            if (! hash_equals($previous->payload_hash, $payload)) {
                $this->conflict('operation_payload_conflict');
            }

            return ['id' => $previous->adjustment_id, 'status' => $previous->status];
        }
        $action = $input['action'];
        $now = $this->clock->now();
        if (in_array($action, ['collect_cod', 'request_refund'], true)) {
            $amount = Vnd::amount((string) $input['amount']);
            $total = Vnd::amount($order->amount);
            if ($action === 'collect_cod') {
                if ($order->method !== 'cod' || ! in_array($order->status, ['shipping', 'completed'], true)
                    || $order->paymentStatus !== 'unpaid' || $amount !== $total
                    || DB::table('payment_adjustments')->where('order_id', $order->id)->where('kind', 'cod')->exists()) {
                    $this->conflict('cod_not_collectable');
                }
                $status = 'collected';
            } else {
                $proven = DB::table('payment_adjustments')->where('order_id', $order->id)->where('kind', 'cod')->where('status', 'collected')->where('amount', $total)->exists()
                    || DB::table('payment_attempts')->where('order_id', $order->id)->where('status', 'paid')->where('amount_vnd', $total)->exists();
                if (! $proven) {
                    $this->conflict('payment_evidence_required');
                }
                $claimed = (int) DB::table('payment_adjustments')->where('order_id', $order->id)->where('kind', 'refund')
                    ->whereIn('status', ['requested', 'settled'])->sum('amount');
                if ($order->paymentStatus !== 'paid' || $amount > $total - $claimed) {
                    $this->conflict('refund_exceeds_collected');
                }
                $status = 'requested';
            }
            $id = DB::table('payment_adjustments')->insertGetId(['order_id' => $order->id, 'kind' => $action === 'collect_cod' ? 'cod' : 'refund',
                'status' => $status, 'amount' => $amount, 'operation_hash' => $hash, 'payload_hash' => $payload,
                'actor_id' => $actor->id, 'reference' => $input['reference'] ?? null, 'reason' => $input['reason'] ?? null,
                'created_at' => $now, 'updated_at' => $now]);
        } elseif (in_array($action, ['settle_refund', 'reject_refund'], true)) {
            $row = DB::table('payment_adjustments')->where('id', $input['refund_id'])->where('order_id', $order->id)->where('kind', 'refund')->lockForUpdate()->first();
            if (! $row || $row->status !== 'requested') {
                $this->conflict('refund_transition_conflict');
            }
            $id = $row->id;
            $status = $action === 'settle_refund' ? 'settled' : 'rejected';
            DB::table('payment_adjustments')->where('id', $id)->update(['status' => $status, 'reference' => $input['reference'] ?? null, 'updated_at' => $now]);
        } else {
            $this->conflict('invalid_operation');
        }
        DB::table('payment_adjustment_events')->insert(['adjustment_id' => $id, 'actor_id' => $actor->id,
            'operation_hash' => $hash, 'payload_hash' => $payload, 'status' => $status,
            'reference' => $input['reference'] ?? null, 'reason' => $input['reason'] ?? null, 'created_at' => $now]);

        return ['id' => $id, 'status' => $status];
    }

    public function history(int $orderId): array
    {
        return DB::table('payment_adjustments')->where('order_id', $orderId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    private function conflict(string $code): never
    {
        throw new InventoryConflict($code, 'Thao tác tiền không phù hợp với số tiền hoặc trạng thái đã ghi nhận.');
    }
}
