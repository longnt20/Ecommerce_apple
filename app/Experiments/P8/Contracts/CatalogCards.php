<?php

namespace App\Experiments\P8\Contracts;

interface CatalogCards
{
    /** @return array<int, array{product_id:int,name:string,slug:string,category_id:?int,sku_count:int,min_price:?string,max_price:?string}> */
    public function page(int $afterId = 0, int $limit = 20): array;
}
