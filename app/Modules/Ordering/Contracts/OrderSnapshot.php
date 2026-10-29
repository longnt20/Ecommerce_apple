<?php

namespace App\Modules\Ordering\Contracts;

use App\Modules\Inventory\Contracts\Hold;
use App\Modules\Payments\Contracts\PaymentOrder;

final readonly class OrderSnapshot
{
    public function __construct(public int $id, public int $userId, public int $warehouseId, public string $status,
        public string $paymentStatus, public string $paymentMethod, public string $amount) {}

    public function payment(?Hold $hold = null): PaymentOrder
    {
        return new PaymentOrder($this->id, $this->amount, $this->paymentMethod, $this->status, $this->paymentStatus, $hold?->status, $hold?->expiresAt);
    }
}
