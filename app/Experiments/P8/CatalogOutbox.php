<?php

namespace App\Experiments\P8;

use App\Models\Product;
use App\Modules\Catalog\Contracts\CatalogProjectionEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class CatalogOutbox implements CatalogProjectionEvents
{
    public function record(int $productId): void
    {
        SandboxGuard::database(DB::connection()->getDatabaseName());
        if (! app()->environment('testing') || DB::transactionLevel() < 1) {
            throw new RuntimeException('p8_testing_transaction_required');
        }
        SandboxGuard::role(DB::connection()->getPdo(), 'producer');
        $product = Product::withTrashed()->whereKey($productId)->lockForUpdate()->firstOrFail();
        DB::table('p8_outbox_streams')->insertOrIgnore(['product_id' => $productId, 'version' => 0]);
        $version = (int) DB::table('p8_outbox_streams')->where('product_id', $productId)->lockForUpdate()->value('version') + 1;
        $totals = $product->variants()->selectRaw('COUNT(*) AS sku_count, MIN(price) AS min_price, MAX(price) AS max_price')->first();
        $event = ['id' => (string) Str::uuid(), 'type' => 'catalog.product-snapshot', 'schema_version' => 1,
            'product_id' => $productId, 'aggregate_version' => $version, 'occurred_at' => now()->utc()->toIso8601String(),
            'payload' => ['name' => $product->name, 'slug' => $product->slug, 'category_id' => $product->category_id,
                'listed' => ! $product->trashed() && $product->status === 'published' && $product->visibility === 'public',
                'sku_count' => (int) $totals->sku_count, 'min_price' => $totals->min_price, 'max_price' => $totals->max_price]];
        // Validate the wire contract before committing the business write.
        Envelope::validate($event);
        DB::table('p8_outbox_streams')->where('product_id', $productId)->update(['version' => $version]);
        DB::table('p8_outbox_messages')->insert(['id' => $event['id'], 'product_id' => $productId,
            'aggregate_version' => $version, 'envelope' => json_encode($event, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
