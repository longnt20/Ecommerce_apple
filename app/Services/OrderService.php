<?php

namespace App\Services;

use App\Exceptions\OrderTransitionConflict;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Models\User;
use App\Services\Ordering\OrderAllocationGuard;
use App\Services\Ordering\OrderTransitionPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
        private OrderTransitionPolicy $policy,
        private OrderAllocationGuard $allocation,
    ) {}

    public function changeStatus(Order $order, string $newStatus, int $actorId, ?string $operationKey = null, ?string $reason = null, ?string $requestId = null): OrderTransition
    {
        $this->assertActor($actorId);
        if (! config('ordering.transitions_enabled') || ! Schema::hasTable('order_transitions')) {
            throw new OrderTransitionConflict('transitions_unavailable', 'Chuyển trạng thái đang tạm dừng để triển khai an toàn.');
        }
        if ($order->getConnection()->getName() !== DB::connection()->getName()) {
            throw new \LogicException('Order and stock must use the same database connection.');
        }
        $operationKey ??= 'legacy-status:'.$newStatus;
        $reason = $reason === null ? null : trim($reason);
        $reason = $reason === '' ? null : $reason;
        if ($operationKey === '' || strlen($operationKey) > 128 || mb_strlen($reason ?? '') > 500) {
            throw new OrderTransitionConflict('invalid_operation', 'Mã thao tác hoặc lý do không hợp lệ.');
        }
        $operationHash = hash('sha256', $actorId.'|'.$operationKey);
        $payloadHash = hash('sha256', json_encode([$order->id, $newStatus, $actorId, $reason], JSON_THROW_ON_ERROR));
        $transition = DB::transaction(function () use ($order, $newStatus, $actorId, $operationHash, $payloadHash, $reason, $requestId) {
            // All order transitions and payment notifications lock the order first.
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertActor($actorId);
            $previous = OrderTransition::where('order_id', $locked->id)->where('operation_hash', $operationHash)->first();
            if ($previous) {
                if (! hash_equals($previous->payload_hash, $payloadHash)) {
                    throw new OrderTransitionConflict('operation_payload_conflict', 'Mã thao tác đã được dùng với nội dung khác.');
                }

                return $previous;
            }
            $this->policy->assertAllowed($locked->status, $newStatus);
            $transition = OrderTransition::create([
                'order_id' => $locked->id, 'actor_id' => $actorId,
                'operation_hash' => $operationHash, 'payload_hash' => $payloadHash,
                'from_status' => $locked->status, 'to_status' => $newStatus,
                'reason' => $reason, 'request_id' => $requestId, 'created_at' => now(),
            ]);
            $allocation = $this->allocation->lock($locked);
            foreach ($allocation as $variant => $quantity) {
                if ($newStatus === 'shipping') {
                    $this->inventoryService->commitOrder($locked->warehouse_id, $variant, $quantity, $locked->id, $transition);
                } elseif ($newStatus === 'cancelled') {
                    $this->inventoryService->releaseOrder($locked->warehouse_id, $variant, $quantity, $locked->id, $transition);
                }
            }
            $changes = ['status' => $newStatus];
            if ($newStatus === 'cancelled') {
                $changes['cancelled_at'] = now();
            } elseif ($newStatus === 'completed') {
                $changes['delivered_at'] = now();
            }
            $locked->update($changes);

            return $transition;
        }, 3);
        $order->refresh();

        return $transition;
    }

    private function assertActor(int $actorId): void
    {
        $actor = User::find($actorId);
        if (! $actor || $actor->role !== 'admin' || $actor->status !== 'active') {
            throw new OrderTransitionConflict('forbidden_actor', 'Tài khoản không có quyền chuyển trạng thái đơn.');
        }
    }
}
