<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('inventory:expire-reservations {--limit=100}', function (\App\Modules\Ordering\Contracts\ReservationExpiry $expiry) {
    if (! config('inventory.enabled')) {
        $this->info('Inventory protocol disabled; no expiry executed.');

        return 0;
    }
    $expired = 0;
    $blocked = 0;
    foreach ($expiry->due(max(1, min(1000, (int) $this->option('limit')))) as $orderId) {
        try {
            $expired += $expiry->expire($orderId) ? 1 : 0;
        } catch (\App\Exceptions\InventoryConflict $exception) {
            $blocked++;
            \Illuminate\Support\Facades\Log::warning('Reservation expiry blocked', ['order_id' => $orderId, 'code' => $exception->reasonCode]);
        }
    }
    $this->info('Expired: '.$expired.'; blocked for reconciliation: '.$blocked);

    return $blocked > 0 ? 1 : 0;
})->purpose('Expire pending reservations atomically; never release paid or confirmed orders');

Schedule::command('inventory:expire-reservations')->everyMinute()->withoutOverlapping();

Artisan::command('inventory:backfill-legacy {--apply}', function (\App\Services\Inventory\LegacyReservationBackfill $backfill) {
    $this->line(json_encode($backfill->run((bool) $this->option('apply')), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
})->purpose('Dry-run legacy reservation classification; metadata-only apply requires maintenance');

Artisan::command('inventory:reconcile', function (\App\Modules\Inventory\Contracts\InventoryMaintenance $reconciliation) {
    $result = $reconciliation->reconcile();
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $result['unexplained'] === [] ? 0 : 1;
})->purpose('Read-only balance, reservation and protocol movement reconciliation');

Artisan::command('coupons:retry-notifications {--limit=100}', function () {
    $ids = \Illuminate\Support\Facades\DB::table('coupon_assignment_deliveries')->where('status', 'pending')
        ->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->pluck('assignment_id');
    foreach ($ids as $id) {
        \App\Jobs\NotifyCouponAssignment::dispatch((int) $id)->afterCommit();
    }
    $this->info('Pending notification jobs submitted: '.$ids->count());
})->purpose('Recover new coupon notices interrupted before queue dispatch; no legacy assignment replay');

Schedule::command('coupons:retry-notifications')->everyFiveMinutes()->withoutOverlapping();
