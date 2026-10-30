<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Display data only. Never cache checkout prices or authoritative stock here. */
final class CatalogReadCache
{
    private ?string $generation = null;

    private array $values = [];

    public function remember(string $family, int $seconds, Closure $load): mixed
    {
        // Neither uncommitted writes nor an old transaction snapshot may enter shared cache.
        if (DB::connection()->transactionLevel() > 0) {
            return $load();
        }
        $this->generation ??= Cache::get('catalog_read_generation', 'initial');
        $key = 'catalog_read:'.$this->generation.':'.$family;

        // Scoped to one request/job, so repeated color accessors do not query DB cache per SKU.
        if (! array_key_exists($key, $this->values)) {
            $this->values[$key] = Cache::remember($key, $seconds, $load);
        }

        return $this->values[$key];
    }

    public function invalidate(): void
    {
        $invalidate = function () {
            Cache::forever('catalog_read_generation', bin2hex(random_bytes(16)));
            $this->generation = null;
            $this->values = [];
        };
        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($invalidate);
        } else {
            $invalidate();
        }
    }
}
