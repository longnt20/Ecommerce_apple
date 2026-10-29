<?php

namespace App\Providers;

use App\Models\ProductAttribute;
use App\Observers\ProductAttributeObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Payments\PaymentGateway::class, \App\Services\Payments\VnPayGateway::class);
        Paginator::useBootstrapFive();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\CartItem::class, \App\Policies\CartItemPolicy::class);
        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('ip:'.$request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('identity:'.hash('sha256', strtolower((string) $request->input('email')).'|'.$request->ip())),
            ];
        });
        ProductAttribute::observe(ProductAttributeObserver::class);
    }
}
