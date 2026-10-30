<?php

namespace App\Modules;

use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $bindings = [
            Catalog\Contracts\CatalogProjectionEvents::class => Catalog\Infrastructure\NoCatalogProjectionEvents::class,
            \App\SharedKernel\Clock::class => \App\SharedKernel\Infrastructure\LaravelClock::class,
            \App\SharedKernel\Transactions::class => \App\SharedKernel\Infrastructure\LaravelTransactions::class,
            Identity\Contracts\AccountAccess::class => Identity\Infrastructure\EloquentAccountAccess::class,
            Catalog\Contracts\SellableSkuReader::class => Catalog\Infrastructure\EloquentSellableSkuReader::class,
            Pricing\Contracts\CouponRedemptions::class => Pricing\Infrastructure\CouponRedemptions::class,
            Pricing\Contracts\Pricing::class => Pricing\Application\QuoteService::class,
            Inventory\Contracts\InventoryOperations::class => Inventory\Infrastructure\InventoryOperations::class,
            Inventory\Contracts\InventoryReservations::class => Inventory\Infrastructure\ReservationManager::class,
            Inventory\Contracts\InventoryMaintenance::class => Inventory\Infrastructure\InventoryMaintenance::class,
            Payments\Contracts\PaymentGateway::class => Payments\Infrastructure\VnPayGateway::class,
            Payments\Contracts\ManualPayments::class => Payments\Infrastructure\ManualPayments::class,
            Inventory\Contracts\ReturnedGoods::class => Inventory\Infrastructure\ReturnedGoods::class,
            Ordering\Contracts\AfterSales::class => Ordering\Application\AfterSales::class,
            Payments\Contracts\PaymentLedger::class => Payments\Infrastructure\EloquentPaymentLedger::class,
            Ordering\Application\Ports\OrderStore::class => Ordering\Infrastructure\EloquentOrderStore::class,
            Ordering\Contracts\LegacyOrders::class => Ordering\Infrastructure\EloquentOrderStore::class,
            Ordering\Contracts\Checkout::class => Ordering\Application\PlaceOrder::class,
            Ordering\Contracts\OrderTransitions::class => Ordering\Application\ChangeOrderStatus::class,
            Ordering\Contracts\PaymentWorkflow::class => Ordering\Application\PaymentWorkflow::class,
            Ordering\Contracts\ReservationExpiry::class => Ordering\Application\ExpireReservations::class,
        ];
        foreach ($bindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
