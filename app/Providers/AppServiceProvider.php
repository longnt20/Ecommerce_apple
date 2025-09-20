<?php

namespace App\Providers;

use App\Models\ProductAttribute;
use App\Observers\ProductAttributeObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ProductAttribute::observe(ProductAttributeObserver::class);
    }
}
