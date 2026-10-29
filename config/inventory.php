<?php

return [
    'enabled' => env('INVENTORY_PROTOCOL_ENABLED', false),
    'online_ttl_minutes' => 15,
    'cod_ttl_minutes' => null,
    'low_stock_threshold' => 10,
    'negative_stock_allowed' => false,
];
