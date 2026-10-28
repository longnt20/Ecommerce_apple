<?php

namespace Tests\Integration\P0;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class CommerceContractTest extends MySqlTestCase
{
    public function test_cod_checkout_and_owned_order_history_contract(): void
    {
        $this->seedCommerce();
        $this->actingAs(User::findOrFail(910001), 'sanctum');
        $response = $this->postJson('/api/checkout', $this->checkoutPayload())
            ->assertOk()->assertJsonStructure(['message', 'order_id', 'final_amount']);
        $id = $response->json('order_id');
        $this->assertEquals(25000000, $response->json('final_amount'));
        $this->assertDatabaseHas('orders', ['id' => $id, 'user_id' => 910001, 'status' => 'pending']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'available_quantity' => 4, 'reserved_quantity' => 1]);
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/orders/'.$id)->assertOk()->assertJsonPath('data.id', $id);
        $this->actingAs(User::findOrFail(910002), 'sanctum');
        $this->getJson('/api/orders/'.$id)->assertNotFound();
        $this->assertSame(1, DB::table('orders')->where('id', $id)->count());
    }
}
