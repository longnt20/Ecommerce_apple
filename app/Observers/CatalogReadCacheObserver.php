<?php

namespace App\Observers;

use App\Services\CatalogReadCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

final class CatalogReadCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void
    {
        app(CatalogReadCache::class)->invalidate();
    }

    public function deleted(Model $model): void
    {
        app(CatalogReadCache::class)->invalidate();
    }

    public function restored(Model $model): void
    {
        app(CatalogReadCache::class)->invalidate();
    }
}
