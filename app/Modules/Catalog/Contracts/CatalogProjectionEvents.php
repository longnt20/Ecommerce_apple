<?php

namespace App\Modules\Catalog\Contracts;

interface CatalogProjectionEvents
{
    public function record(int $productId): void;
}
