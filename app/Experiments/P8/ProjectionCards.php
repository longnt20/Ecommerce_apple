<?php

namespace App\Experiments\P8;

use App\Experiments\P8\Contracts\CatalogCards;
use PDO;

final class ProjectionCards implements CatalogCards
{
    public function __construct(private PDO $pdo)
    {
        SandboxGuard::role($pdo, 'consumer');
    }

    public function page(int $afterId = 0, int $limit = 20): array
    {
        $query = $this->pdo->prepare('SELECT product_id,name,slug,category_id,sku_count,min_price,max_price FROM p8_catalog_cards WHERE listed=1 AND product_id>? ORDER BY product_id LIMIT '.max(1, min(100, $limit)));
        $query->execute([$afterId]);

        return array_map(function ($row) {
            $row['product_id'] = (int) $row['product_id'];
            $row['category_id'] = $row['category_id'] === null ? null : (int) $row['category_id'];
            $row['sku_count'] = (int) $row['sku_count'];

            return $row;
        }, $query->fetchAll());
    }
}
