<?php

require __DIR__.'/../../vendor/autoload.php';

putenv('P0_DB_MODE=disabled');
Tests\Support\TestApplication::create();
$sandbox = new Tests\Support\P7Sandbox;
try {
    $sandbox->catalog();
    $queries = [
        'rejected_correlated_variant_totals' => App\Models\ProductVariant::where('product_id', 1000001)
            ->withSum('inventory as stock_quantity', 'quantity')->withSum('inventory as available_stock', 'available_quantity'),
        'detail_stock' => App\Models\Product::findOrFail(1000001)->inventories(),
        'warehouse_page' => app(App\Reporting\InventoryReadQuery::class)->filtered(Illuminate\Http\Request::create('/', 'GET', ['warehouse_id' => 1]))->limit(10),
        'export_chunk' => Illuminate\Support\Facades\DB::table('inventory')->where('id', '>', 3000000)->orderBy('id')->limit(1000),
    ];
    $plans = [];
    foreach ($queries as $name => $query) {
        $plans[$name] = Illuminate\Support\Facades\DB::select('EXPLAIN '.$query->toSql(), $query->getBindings());
    }
    file_put_contents(dirname(__DIR__, 2).'/docs/architecture/p7-query-plans.json', json_encode($plans, JSON_PRETTY_PRINT)."\n");
    echo json_encode($plans, JSON_PRETTY_PRINT);
} finally {
    $sandbox->close();
}
