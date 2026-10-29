<?php

namespace Tests\Support;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class P3Fixture
{
    public static function seed(): Order
    {
        foreach ([910001 => 'user', 910002 => 'admin'] as $id => $role) {
            DB::table('users')->insert(['id' => $id, 'name' => 'P3 synthetic '.$id, 'email' => $id.'@p3.example.test',
                'password' => 'synthetic-only', 'code' => 'P3-'.$id, 'status' => 'active', 'role' => $role]);
        }
        DB::table('categories')->insert(['id' => 910001, 'name' => 'P3 category', 'slug' => 'p3-category']);
        DB::table('products')->insert(['id' => 910001, 'category_id' => 910001, 'name' => 'P3 phone', 'slug' => 'p3-phone', 'status' => 'published', 'visibility' => 'public']);
        DB::table('product_variants')->insert(['id' => 910001, 'product_id' => 910001, 'sku' => 'P3-SKU', 'price' => 25000000]);
        foreach ([1, 2] as $warehouse) {
            DB::table('warehouses')->insert(['id' => $warehouse, 'code' => 'P3-WH-'.$warehouse, 'name' => 'P3 warehouse', 'address' => 'Synthetic', 'city' => 'Synthetic']);
            DB::table('inventory')->insert(['id' => 910000 + $warehouse, 'warehouse_id' => $warehouse, 'product_variant_id' => 910001,
                'quantity' => 5, 'reserved_quantity' => $warehouse === 1 ? 1 : 0, 'available_quantity' => $warehouse === 1 ? 4 : 5]);
        }
        DB::table('orders')->insert(['id' => 910001, 'user_id' => 910001, 'warehouse_id' => 1, 'code' => 'P3-ORDER',
            'total_price' => 25000000, 'final_amount' => 25000000, 'status' => 'pending', 'payment_method' => 'COD']);
        DB::table('order_items')->insert(['id' => 910001, 'order_id' => 910001, 'product_id' => 910001, 'variant_id' => 910001,
            'product_name' => 'P3 phone', 'quantity' => 1, 'price' => 25000000, 'total' => 25000000]);
        DB::table('inventory_transactions')->insert(['id' => 910001, 'transaction_code' => 'P3-RESERVE', 'warehouse_id' => 1,
            'product_variant_id' => 910001, 'type' => 'reserve', 'quantity' => 1, 'before_quantity' => 5, 'after_quantity' => 5,
            'reference_type' => 'order', 'reference_id' => 910001, 'status' => 'completed', 'created_by' => 910001]);

        return Order::findOrFail(910001);
    }

    public static function addSecondSku(): void
    {
        DB::table('product_variants')->insert(['id' => 910002, 'product_id' => 910001, 'sku' => 'P3-SKU-2', 'price' => 25000000]);
        DB::table('inventory')->insert(['id' => 910003, 'warehouse_id' => 1, 'product_variant_id' => 910002, 'quantity' => 5, 'reserved_quantity' => 1, 'available_quantity' => 4]);
        DB::table('order_items')->insert(['id' => 910002, 'order_id' => 910001, 'product_id' => 910001, 'variant_id' => 910002, 'product_name' => 'P3 second SKU', 'quantity' => 1, 'price' => 25000000, 'total' => 25000000]);
        DB::table('inventory_transactions')->insert(['id' => 910002, 'transaction_code' => 'P3-SECOND-RESERVE', 'warehouse_id' => 1, 'product_variant_id' => 910002,
            'type' => 'reserve', 'quantity' => 1, 'before_quantity' => 5, 'after_quantity' => 5, 'reference_type' => 'order', 'reference_id' => 910001]);
        DB::table('orders')->where('id', 910001)->update(['total_price' => 50000000, 'final_amount' => 50000000]);
        $reservation = \App\Models\InventoryReservation::where('order_id', 910001)->first();
        if ($reservation) {
            $reservation->items()->create(['order_item_id' => 910002, 'warehouse_id' => 1, 'product_variant_id' => 910002,
                'quantity' => 1, 'status' => 'active']);
        }
    }
}
