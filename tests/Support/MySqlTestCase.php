<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class MySqlTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (TestingEnvironment::database()['mode'] !== 'mysql') {
            $this->markTestSkipped('P0 MySQL integration blocked: provision a dedicated existing schema/account and configure .env.testing. No migration/import is automatic.');
        }

        parent::setUp();
        DB::beginTransaction();
        $this->beforeApplicationDestroyed(static function () {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    protected function seedCommerce(): void
    {
        $timestamp = '2026-01-01 00:00:00';
        foreach ([910001, 910002] as $id) {
            DB::table('users')->insert([
                'id' => $id, 'name' => 'P0 synthetic '.$id, 'email' => $id.'@example.test',
                'password' => 'not-a-login-credential', 'role' => 'user', 'code' => 'P0-'.$id,
                'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp,
            ]);
            DB::table('carts')->insert(['id' => $id, 'user_id' => $id]);
        }
        DB::table('categories')->insert(['id' => 910001, 'name' => 'P0 category', 'slug' => 'p0-category']);
        DB::table('products')->insert([
            'id' => 910001, 'name' => 'P0 phone', 'slug' => 'p0-phone', 'category_id' => 910001,
            'default_price' => '25000000.00', 'visibility' => 'public', 'status' => 'published',
        ]);
        DB::table('product_variants')->insert([
            'id' => 910001, 'product_id' => 910001, 'sku' => 'P0-SKU', 'price' => '25000000.00',
            'cost_price' => '15000000.00', 'thumbnail' => 'p0-fixture.webp',
        ]);
        foreach ([1, 2] as $id) {
            DB::table('warehouses')->insert([
                'id' => $id, 'code' => 'P0-WH-'.$id, 'name' => 'P0 warehouse '.$id,
                'address' => 'Synthetic address', 'city' => 'Synthetic city', 'is_active' => true,
            ]);
            DB::table('inventory')->insert([
                'id' => 910000 + $id, 'warehouse_id' => $id, 'product_variant_id' => 910001,
                'quantity' => 5, 'available_quantity' => 5, 'reserved_quantity' => 0,
            ]);
        }
    }

    protected function checkoutPayload(string $price = '25000000.00'): array
    {
        return [
            'payment_method' => 'cod',
            'items' => [['product_id' => 910001, 'variant_id' => 910001, 'quantity' => 1, 'price' => $price]],
            'fullname' => 'P0 synthetic customer', 'phone' => '0000000000',
            'address' => 'Synthetic address', 'ward' => 'Test ward', 'district' => 'Test district', 'province' => 'Test province',
        ];
    }
}
