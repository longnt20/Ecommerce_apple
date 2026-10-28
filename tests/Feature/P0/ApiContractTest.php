<?php

namespace Tests\Feature\P0;

use App\Models\User;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    public function test_root_exposes_the_current_api_info_contract(): void
    {
        $this->getJson('/')->assertOk()->assertExactJson([
            'message' => 'Apple E-commerce API',
            'version' => '1.0.0',
            'frontend' => 'Running on separate Vue application',
            'endpoints' => [
                'auth' => '/api/login, /api/register, /api/user',
                'products' => '/api/products', 'cart' => '/api/cart', 'orders' => '/api/orders',
            ],
        ]);
    }

    public function test_checkout_and_order_history_require_authentication(): void
    {
        $this->postJson('/api/checkout', [])->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/orders/910001')->assertUnauthorized();
        $this->getJson('/api/cart')->assertUnauthorized();
    }

    public function test_authenticated_empty_checkout_returns_validation_errors_without_database_access(): void
    {
        $user = new User;
        $user->forceFill(['id' => 910001, 'name' => 'P0 synthetic user', 'role' => 'user', 'status' => 'active']);
        $this->actingAs($user, 'sanctum')->postJson('/api/checkout', [])
            ->assertUnprocessable()->assertJsonValidationErrors([
                'payment_method', 'items', 'fullname', 'phone', 'address', 'ward', 'district', 'province',
            ]);
    }
}
