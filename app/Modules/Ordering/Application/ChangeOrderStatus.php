<?php

namespace App\Modules\Ordering\Application;

use App\Exceptions\InventoryConflict;
use App\Exceptions\OrderTransitionConflict;
use App\Modules\Identity\Contracts\AccountAccess;
use App\Modules\Inventory\Contracts\InventoryReservations;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\Modules\Ordering\Contracts\OrderTransitions;
use App\Modules\Ordering\Contracts\TransitionReceipt;
use App\Modules\Ordering\Domain\OrderTransitionPolicy;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use App\SharedKernel\Transactions;

final class ChangeOrderStatus implements OrderTransitions
{
    public function __construct(private OrderStore $orders, private AccountAccess $accounts, private InventoryReservations $reservations,
        private OrderTransitionPolicy $policy, private Transactions $transactions, private Clock $clock, private \App\Modules\Pricing\Contracts\CouponRedemptions $coupons) {}

    private function authorize(ActorContext $actor): void
    {
        if (! $this->accounts->administrator($actor)) {
            throw new OrderTransitionConflict('forbidden_actor', 'Tài khoản không có quyền chuyển trạng thái đơn.');
        }
    }

    public function change(int $orderId, string $target, ActorContext $actor, ?string $key = null, ?string $reason = null, ?string $requestId = null): TransitionReceipt
    {
        $this->authorize($actor);
        if (! $this->orders->enabled()) {
            throw new OrderTransitionConflict('transitions_unavailable', 'Chuyển trạng thái đang tạm dừng để triển khai an toàn.');
        }
        $key ??= 'legacy-status:'.$target;
        $reason = $reason === null ? null : trim($reason);
        $reason = $reason === '' ? null : $reason;
        if ($key === '' || strlen($key) > 128 || mb_strlen($reason ?? '') > 500) {
            throw new OrderTransitionConflict('invalid_operation', 'Mã thao tác hoặc lý do không hợp lệ.');
        }
        $operationHash = hash('sha256', $actor->id.'|'.$key);
        $payloadHash = hash('sha256', json_encode([$orderId, $target, $actor->id, $reason], JSON_THROW_ON_ERROR));

        return $this->transactions->run(function () use ($orderId, $target, $actor, $operationHash, $payloadHash, $reason, $requestId) {
            $order = $this->orders->snapshot($orderId, true);
            $this->authorize($actor);
            $previous = $this->orders->previous($orderId, $operationHash);
            if ($previous) {
                if (! hash_equals($previous->payloadHash, $payloadHash)) {
                    throw new OrderTransitionConflict('operation_payload_conflict', 'Mã thao tác đã được dùng với nội dung khác.');
                }

                return $previous;
            }
            $this->policy->assertAllowed($order->status, $target);
            $receipt = $this->orders->record($orderId, $actor, $order->status, $target, $operationHash, $payloadHash, $reason, $requestId, $this->clock->now());
            try {
                $this->reservations->transition($this->orders->allocation($order), $receipt->id, $target, $actor);
            } catch (InventoryConflict $error) {
                throw new OrderTransitionConflict($error->reasonCode, $error->getMessage());
            }
            if ($target === 'cancelled') {
                $this->coupons->finish($orderId, false);
            } elseif ($target === 'confirmed' && $order->paymentMethod === 'cod') {
                $this->coupons->finish($orderId, true);
            }
            $this->orders->setStatus($orderId, $target, $this->clock->now());

            return $receipt;
        });
    }
}
