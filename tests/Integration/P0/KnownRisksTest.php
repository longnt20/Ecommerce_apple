<?php

namespace Tests\Integration\P0;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class KnownRisksTest extends MySqlTestCase
{
    public function test_c1_client_zero_price_is_currently_accepted(): void
    {
        $this->seedCommerce();
        $this->actingAs(User::findOrFail(910001), 'sanctum');
        $response = $this->postJson('/api/checkout', $this->checkoutPayload('0'))->assertOk();
        $this->assertEquals(0, $response->json('final_amount'));
        $this->markTestIncomplete('C1 reproduced; owner P2: client price still controls order amount. This is not a security pass.');
    }

    public function test_c2_unsigned_public_return_currently_marks_an_order_paid(): void
    {
        $this->seedCommerce();
        DB::table('orders')->insert(['id' => 910001, 'user_id' => 910001, 'warehouse_id' => 1, 'code' => 'P0-UNSIGNED', 'payment_method' => 'VNPAY', 'payment_status' => 'unpaid']);
        $this->get('/api/vnpay/return?vnp_TxnRef=910001&vnp_Amount=100&vnp_ResponseCode=00&vnp_TransactionNo=P0-FAKE')
            ->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => 910001, 'payment_status' => 'paid']);
        $this->markTestIncomplete('C2 reproduced; owner P2: unsigned return changes payment state. This is not a security pass.');
    }

    public function test_c3_checkout_reservation_can_differ_from_the_order_warehouse(): void
    {
        $this->seedCommerce();
        DB::table('inventory')->where('id', 910001)->delete();
        $this->actingAs(User::findOrFail(910001), 'sanctum');
        $response = $this->postJson('/api/checkout', $this->checkoutPayload())->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $response->json('order_id'), 'warehouse_id' => 1]);
        $this->assertDatabaseHas('inventory_transactions', ['reference_id' => $response->json('order_id'), 'type' => 'reserve', 'warehouse_id' => 2]);
        $this->markTestIncomplete('C3 reproduced; owner P4: order warehouse differs from reserved warehouse. This is not a consistency pass.');
    }

    public function test_h2_another_users_cart_item_can_currently_be_updated(): void
    {
        $this->seedCommerce();
        DB::table('cart_items')->insert([
            'id' => 910002, 'cart_id' => 910002, 'product_id' => 910001,
            'product_variant_id' => 910001, 'quantity' => 1, 'price_at_add' => '25000000.00',
        ]);
        $this->actingAs(User::findOrFail(910001), 'sanctum')
            ->putJson('/api/cart/item/910002', ['quantity' => 3])->assertOk();
        $this->assertDatabaseHas('cart_items', ['id' => 910002, 'cart_id' => 910002, 'quantity' => 3]);
        $this->markTestIncomplete('H2 reproduced; owner P2: cart update lacks ownership enforcement. This is not an authorization pass.');
    }
}
