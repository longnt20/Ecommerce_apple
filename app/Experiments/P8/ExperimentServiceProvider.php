<?php

namespace App\Experiments\P8;

use App\Modules\Catalog\Contracts\CatalogProjectionEvents;
use App\Modules\Catalog\Infrastructure\NoCatalogProjectionEvents;
use Illuminate\Support\ServiceProvider;

final class ExperimentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CatalogProjectionEvents::class, fn () => config('p8.enabled')
            ? new CatalogOutbox : new NoCatalogProjectionEvents);
    }
}
