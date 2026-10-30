<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Modules\Catalog\Contracts\CatalogProjectionEvents;

final class NoCatalogProjectionEvents implements CatalogProjectionEvents
{
    public function record(int $productId): void {}
}
