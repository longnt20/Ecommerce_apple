<?php

namespace App\Services\Inventory;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;

class ExpireReservations
{
    public function __construct(private ReservationManager $reservations, private StockWriter $writer) {}

    public function expire(int $orderId): bool
    {
        $this->writer->assertEnabled();

        return DB::transaction(function () use ($orderId) {
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $attempt = PaymentAttempt::where('order_id', $orderId)->lockForUpdate()->first();
            $reservation = InventoryReservation::where('order_id', $orderId)->lockForUpdate()->first();
            if (! $reservation || $reservation->status !== 'active' || $order->status !== 'pending'
                || $reservation->expires_at === null || $reservation->expires_at->isFuture()) {
                return false;
            }
            if ($order->payment_status === 'paid' || $attempt?->status === 'paid') {
                $reservation->update(['expires_at' => null]);

                return false;
            }
            $this->reservations->validate($order, 'cancelled');
            $transition = OrderTransition::create([
                'order_id' => $orderId, 'actor_id' => null, 'from_status' => 'pending', 'to_status' => 'cancelled',
                'operation_hash' => hash('sha256', 'system-expiry:'.$reservation->id),
                'payload_hash' => hash('sha256', json_encode([$orderId, $reservation->id, 'expired'])),
                'reason' => 'Reservation expired', 'created_at' => now(),
            ]);
            $this->reservations->consume($order, $reservation, $transition->id, 'expired', null);
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return true;
        }, 3);
    }

    public function due(int $limit = 100): array
    {
        return InventoryReservation::where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->orderBy('order_id')->limit($limit)->pluck('order_id')->map(fn ($id) => (int) $id)->all();
    }
}
