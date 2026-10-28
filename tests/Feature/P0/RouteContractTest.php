<?php

namespace Tests\Feature\P0;

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Tests\TestCase;

class RouteContractTest extends TestCase
{
    public function test_checkout_and_order_actions_keep_their_current_methods_and_authentication(): void
    {
        foreach ([
            ['POST', '/api/checkout', CheckoutController::class.'@process'],
            ['GET', '/api/orders', OrderController::class.'@index'],
            ['GET', '/api/orders/910001', OrderController::class.'@show'],
        ] as [$method, $uri, $action]) {
            $route = $this->app['router']->getRoutes()->match(Request::create($uri, $method));
            $this->assertSame($action, $route->getActionName());
            $this->assertContains('auth:sanctum', $route->middleware());
        }
    }

    public function test_both_existing_vnpay_return_routes_remain_public_in_the_baseline(): void
    {
        foreach (['/api/vnpay/return', '/vnpay/return'] as $uri) {
            $route = $this->app['router']->getRoutes()->match(Request::create($uri));
            $this->assertSame(CheckoutController::class.'@vnpReturn', $route->getActionName());
            $this->assertNotContains('auth:sanctum', $route->middleware());
        }
        // Public provider notifications need signature verification in P2, not a user login.
    }
}
