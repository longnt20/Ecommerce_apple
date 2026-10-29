<?php

namespace Tests\Integration\P2;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class CheckoutSecurityTest extends MySqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerce();
        DB::table('products')->insert([
            'id' => 910002, 'name' => 'P2 other phone', 'slug' => 'p2-other-phone', 'category_id' => 910001,
            'default_price' => 25000000, 'status' => 'published', 'visibility' => 'public',
        ]);
        $this->actingAs(User::findOrFail(910001), 'sanctum');
    }

    public function test_server_price_snapshot_ignores_client_price_and_cost(): void
    {
        $payload = $this->checkoutPayload('0');
        $payload['items'][0]['quantity'] = 2;
        $id = $this->postJson('/api/checkout', $payload)->assertOk()->assertJsonPath('final_amount', 50000000)->json('order_id');
        $this->assertDatabaseHas('order_items', ['order_id' => $id, 'price' => '25000000.00', 'total' => '50000000.00']);
    }

    /** @dataProvider invalidCatalog */
    public function test_unsellable_or_mismatched_sku_is_rejected(string $case): void
    {
        $payload = $this->checkoutPayload();
        match ($case) {
            'mismatch' => $payload['items'][0]['product_id'] = 910002,
            'hidden' => DB::table('products')->where('id', 910001)->update(['visibility' => 'hidden']),
            'draft' => DB::table('products')->where('id', 910001)->update(['status' => 'draft']),
            'deleted' => DB::table('product_variants')->where('id', 910001)->update(['deleted_at' => now()]),
            'fraction' => DB::table('product_variants')->where('id', 910001)->update(['price' => '123.45']),
            'duplicate' => $payload['items'][] = $payload['items'][0],
        };
        $this->postJson('/api/checkout', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'reserved_quantity' => 0]);
    }

    public static function invalidCatalog(): array
    {
        return array_map(fn ($case) => [$case], ['mismatch', 'hidden', 'draft', 'deleted', 'fraction', 'duplicate']);
    }

    public function test_missing_stock_rolls_back_the_order(): void
    {
        DB::table('inventory')->delete();
        $this->postJson('/api/checkout', $this->checkoutPayload())->assertStatus(400);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_buy_now_preserves_cart_and_cart_checkout_only_consumes_purchased_quantity(): void
    {
        DB::table('cart_items')->insert([
            'id' => 910001, 'cart_id' => 910001, 'product_id' => 910001,
            'product_variant_id' => 910001, 'quantity' => 4, 'price_at_add' => 1,
        ]);
        $payload = $this->checkoutPayload();
        $payload['mode'] = 'buy-now';
        $this->postJson('/api/checkout', $payload)->assertOk();
        $this->assertDatabaseHas('cart_items', ['id' => 910001, 'quantity' => 4]);
        $payload['mode'] = 'cart';
        $payload['idempotency_key'] = (string) \Illuminate\Support\Str::uuid();
        $this->postJson('/api/checkout', $payload)->assertOk();
        $this->assertDatabaseHas('cart_items', ['id' => 910001, 'quantity' => 3]);
    }

    public function test_cart_price_and_ownership(): void
    {
        $this->postJson('/api/cart/add', ['product_id' => 910001, 'variant_id' => 910001, 'quantity' => 1])
            ->assertOk()->assertJsonPath('items.0.price_at_add', '25000000.00');
        $own = DB::table('cart_items')->where('cart_id', 910001)->value('id');
        $this->putJson('/api/cart/item/'.$own, ['quantity' => 2])->assertOk();
        DB::table('cart_items')->insert([
            'id' => 910002, 'cart_id' => 910002, 'product_id' => 910001,
            'product_variant_id' => 910001, 'quantity' => 1, 'price_at_add' => 25000000,
        ]);
        $this->deleteJson('/api/cart/item/910002')->assertForbidden();
        $this->assertDatabaseHas('cart_items', ['id' => 910002, 'quantity' => 1]);
        $this->deleteJson('/api/cart/item/'.$own)->assertOk();
        $this->postJson('/api/cart/add', ['product_id' => 910002, 'variant_id' => 910001, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_blocked_account_cannot_use_an_existing_token_or_login(): void
    {
        $user = User::findOrFail(910001);
        $user->update(['password' => 'Synthetic-password-123']);
        $token = $user->createToken('p2-test')->plainTextToken;
        $user->update(['status' => 'blocked']);
        // Reset the guard installed by actingAs; exercise actual bearer resolution.
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/cart')->assertForbidden();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Synthetic-password-123'])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'nobody@example.test', 'password' => 'synthetic-wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/login', ['email' => 'nobody@example.test', 'password' => 'synthetic-wrong'])->assertStatus(429);
    }

    public function test_disabled_online_payment_fails_before_creating_order_or_reserving_stock(): void
    {
        config(['vnpay.enabled' => false]);
        $payload = $this->checkoutPayload();
        $payload['payment_method'] = 'vnpay';
        $this->postJson('/api/checkout', $payload)->assertStatus(503);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'reserved_quantity' => 0]);
    }

    public function test_product_detail_uses_selling_price_and_does_not_expose_cost(): void
    {
        $this->getJson('/api/products/p0-phone')->assertOk()
            ->assertJsonPath('data.selected_variant.final_price', '25.000.000')
            ->assertJsonPath('data.variants.0.final_price', '25.000.000');
        $this->getJson('/api/products/910001/variants')->assertOk()->assertJsonMissingPath('0.cost_price');
    }
}
