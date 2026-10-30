<?php

namespace App\Modules\Ordering\Application;

use App\Exceptions\InventoryConflict;
use App\Modules\Identity\Contracts\AccountAccess;
use App\Modules\Inventory\Contracts\ReturnedGoods;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\Modules\Ordering\Contracts\AfterSales as Contract;
use App\Modules\Payments\Contracts\ManualPayments;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use App\SharedKernel\Transactions;
use App\SharedKernel\Vnd;

final class AfterSales implements Contract
{
    public function __construct(private OrderStore $orders, private ManualPayments $payments, private ReturnedGoods $returns,
        private AccountAccess $accounts, private Transactions $transactions, private Clock $clock) {}

    public function money(int $orderId, ActorContext $actor, string $key, array $input): array
    {
        $this->authorize($actor, $key);
        $input = array_intersect_key($input, array_flip(['action', 'amount', 'refund_id', 'reference', 'reason']));
        $action = $input['action'] ?? '';
        if (! in_array($action, ['collect_cod', 'request_refund', 'settle_refund', 'reject_refund'], true)) {
            throw new InventoryConflict('invalid_operation', 'Loại thao tác tiền không hợp lệ.');
        }
        if (in_array($action, ['collect_cod', 'request_refund'], true)) {
            try {
                $input['amount'] = Vnd::amount((string) ($input['amount'] ?? ''));
            } catch (\InvalidArgumentException) {
                throw new InventoryConflict('invalid_amount', 'Số tiền cần là số nguyên VND dương.');
            }
        } elseif (filter_var($input['refund_id'] ?? null, FILTER_VALIDATE_INT) < 1) {
            throw new InventoryConflict('invalid_refund', 'Cần mã yêu cầu hoàn tiền.');
        }
        if (isset($input['refund_id'])) {
            $input['refund_id'] = (int) $input['refund_id'];
        }
        foreach (['reference' => 128, 'reason' => 500] as $field => $max) {
            if (isset($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
            if (mb_strlen($input[$field] ?? '') > $max) {
                throw new InventoryConflict('invalid_operation', 'Thông tin đối soát quá dài.');
            }
        }
        if ((in_array($action, ['collect_cod', 'settle_refund'], true) && empty($input['reference']))
            || ($action !== 'collect_cod' && empty($input['reason']))) {
            throw new InventoryConflict('reconciliation_required', 'Cần mã đối soát hoặc lý do xử lý.');
        }
        ksort($input);
        try {
            return $this->transactions->run(function () use ($orderId, $actor, $key, $input, $action) {
                $order = $this->orders->snapshot($orderId, true);
                $this->authorize($actor, $key);
                $result = $this->payments->record($order->payment(), $actor, $key, $input);
                if ($action === 'collect_cod' && $order->paymentStatus !== 'paid') {
                    $this->orders->markPaid($orderId, $input['reference'], $this->clock->now());
                }

                return $result;
            });
        } catch (\Throwable $error) {
            if ($this->transactions->isUniqueViolation($error)) {
                throw new InventoryConflict('reconciliation_reference_conflict', 'Mã đối soát hoặc operation đã được sử dụng.');
            }
            throw $error;
        }
    }

    public function receive(int $orderId, ActorContext $actor, string $key, array $input): array
    {
        $this->authorize($actor, $key);
        $input = array_intersect_key($input, array_flip(['order_item_id', 'quantity', 'disposition', 'reference', 'reason']));
        $input['reference'] = trim($input['reference'] ?? '');
        $input['reason'] = trim($input['reason'] ?? '');
        if (filter_var($input['order_item_id'] ?? null, FILTER_VALIDATE_INT) < 1
            || filter_var($input['quantity'] ?? null, FILTER_VALIDATE_INT) < 1
            || ! in_array($input['disposition'] ?? '', ['sellable', 'damaged'], true)
            || empty($input['reference']) || empty($input['reason']) || mb_strlen($input['reference']) > 128 || mb_strlen($input['reason']) > 500) {
            throw new InventoryConflict('return_inspection_required', 'Cần mã biên nhận và kết quả kiểm tra hàng trả.');
        }
        $input['order_item_id'] = (int) $input['order_item_id'];
        $input['quantity'] = (int) $input['quantity'];
        ksort($input);

        try {
            return $this->transactions->run(function () use ($orderId, $actor, $key, $input) {
                $order = $this->orders->snapshot($orderId, true);
                $this->authorize($actor, $key);
                if (! in_array($order->status, ['shipping', 'completed'], true)) {
                    throw new InventoryConflict('return_not_shipped', 'Chỉ nhận trả đơn đã xuất kho.');
                }
                foreach ($this->orders->allocation($order)->lines as $line) {
                    if ($line->orderItemId === (int) $input['order_item_id']) {
                        return $this->returns->receive($orderId, $line->orderItemId, $order->warehouseId, $line->variantId, $line->quantity, $actor, $key, $input);
                    }
                }
                throw new InventoryConflict('foreign_order_item', 'Sản phẩm trả không thuộc đơn này.');
            });
        } catch (\Throwable $error) {
            if ($this->transactions->isUniqueViolation($error)) {
                throw new InventoryConflict('return_reference_conflict', 'Mã biên nhận hoặc operation đã được sử dụng.');
            }
            throw $error;
        }
    }

    public function history(int $orderId, ActorContext $actor): array
    {
        $this->authorize($actor, 'history');
        $order = $this->orders->snapshot($orderId);

        return ['order_id' => $orderId, 'payment_status' => $order->paymentStatus,
            'payments' => $this->payments->history($orderId), 'returns' => $this->returns->history($orderId)];
    }

    private function authorize(ActorContext $actor, string $key): void
    {
        if (! $this->accounts->administrator($actor)) {
            throw new InventoryConflict('forbidden_actor', 'Chỉ quản trị viên được đối soát tiền/hàng.');
        }
        if (trim($key) === '' || strlen($key) > 128) {
            throw new InventoryConflict('invalid_operation', 'Cần mã thao tác tối đa 128 ký tự.');
        }
    }
}
