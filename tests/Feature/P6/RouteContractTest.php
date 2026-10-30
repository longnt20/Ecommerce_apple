<?php

namespace Tests\Feature\P6;

use App\Http\Controllers\Admin\AfterSalesController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use Tests\TestCase;

class RouteContractTest extends TestCase
{
    public function test_p6_routes_have_real_public_handlers_and_admin_middleware(): void
    {
        $owners = [AfterSalesController::class, CouponController::class, InventoryController::class,
            ProductController::class, ProductVariantController::class, UserController::class, WarehouseController::class];
        $count = 0;
        foreach ($this->app['router']->getRoutes() as $route) {
            [$controller, $method] = array_pad(explode('@', $route->getActionName()), 2, null);
            if (! in_array($controller, $owners, true)) {
                continue;
            }
            $this->assertTrue(method_exists($controller, $method), $route->uri().' missing handler');
            $this->assertTrue((new \ReflectionMethod($controller, $method))->isPublic());
            $this->assertContains('auth', $route->middleware());
            $this->assertContains('is_admin', $route->middleware());
            $count++;
        }
        $this->assertGreaterThan(60, $count);
    }
}
