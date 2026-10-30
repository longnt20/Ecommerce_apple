<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/** Disposable synthetic databases; the application database is never a write target. */
final class P7Sandbox
{
    public DatabaseSandbox $admin;

    public array $connection;

    public function __construct()
    {
        $this->admin = new DatabaseSandbox;
        $name = 'apple_project_p1_fresh_'.bin2hex(random_bytes(6));
        $pdo = $this->admin->create($name);
        DatabaseSandbox::loadSnapshot($pdo, file_get_contents(database_path('schema/mysql-schema.sql')));
        $this->connection = $this->admin->connectionConfig($name);
        config(['database.connections.p7' => $this->connection]);
        DB::setDefaultConnection('p7');
        foreach (['2026_10_28_000001_reconcile_application_schema.php', '2026_10_29_000001_create_verified_payments.php',
            '2026_10_29_000002_create_order_transitions.php', '2026_10_29_000003_create_inventory_protocol.php',
            '2026_10_29_000004_create_inventory_legacy_holds.php', '2026_10_30_000001_create_commerce_lifecycle.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    public function close(): void
    {
        DB::purge('p7');
        $this->admin->dropCreated();
    }

    public function catalog(int $products = 1000, int $variants = 3, int $warehouses = 5, ?int $stockLimit = null): void
    {
        $timestamp = '2026-10-30 00:00:00';
        DB::table('users')->insert(['id' => 910002, 'name' => 'P7 synthetic admin', 'email' => 'p7@example.test',
            'password' => 'not-a-login-credential', 'role' => 'admin', 'code' => 'P7-ADMIN', 'status' => 'active']);
        DB::table('categories')->insert(['id' => 910001, 'name' => 'P7 phones', 'slug' => 'p7-phones']);
        DB::table('product_attributes')->insert(['type' => 'color', 'value' => 'black', 'label' => 'P7 Black', 'is_active' => true]);
        for ($warehouse = 1; $warehouse <= $warehouses; $warehouse++) {
            DB::table('warehouses')->insert(['id' => $warehouse, 'code' => 'P7-WH-'.$warehouse, 'name' => 'P7 warehouse '.$warehouse,
                'address' => 'Synthetic address', 'city' => 'Synthetic city', 'is_active' => true]);
        }
        $stocks = 0;
        for ($start = 1; $start <= $products; $start += 100) {
            $productRows = $variantRows = $stockRows = [];
            for ($index = $start; $index < min($start + 100, $products + 1); $index++) {
                $productId = 1000000 + $index;
                $productRows[] = ['id' => $productId, 'name' => 'P7 phone '.$index, 'slug' => 'p7-phone-'.$index,
                    'category_id' => 910001, 'visibility' => 'public', 'status' => 'published', 'default_price' => 25000000,
                    'created_at' => $timestamp, 'updated_at' => $timestamp];
                for ($variant = 1; $variant <= $variants; $variant++) {
                    $variantId = 2000000 + ($index - 1) * $variants + $variant;
                    $variantRows[] = ['id' => $variantId, 'product_id' => $productId, 'sku' => 'P7-SKU-'.$variantId,
                        'price' => 25000000, 'cost_price' => 15000000, 'color' => 'black', 'storage' => '128GB',
                        'created_at' => $timestamp, 'updated_at' => $timestamp];
                    for ($warehouse = 1; $warehouse <= $warehouses && ($stockLimit === null || $stocks < $stockLimit); $warehouse++) {
                        $stocks++;
                        $stockRows[] = ['id' => 3000000 + $stocks, 'warehouse_id' => $warehouse, 'product_variant_id' => $variantId,
                            'quantity' => 10, 'reserved_quantity' => 0, 'available_quantity' => 10, 'created_at' => $timestamp, 'updated_at' => $timestamp];
                    }
                }
            }
            DB::table('products')->insert($productRows);
            DB::table('product_variants')->insert($variantRows);
            foreach (array_chunk($stockRows, 1000) as $chunk) {
                DB::table('inventory')->insert($chunk);
            }
        }
    }
}
