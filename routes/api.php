<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
// Những route cần token mới truy cập
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'profile']);
});
Route::apiResource('/categories', CategoryController::class);
Route::apiResource('/promotions', PromotionController::class);
Route::get('/promotion-categories',[PromotionController::class, 'promotionCategories']);

Route::get('/home',[HomeController::class, 'homeProducts']);

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/cart", [CartController::class, "getCart"]);
    Route::post("/cart/add", [CartController::class, "add"]);
    Route::put("/cart/item/{item}", [CartController::class, "update"]);
    Route::delete("/cart/item/{item}", [CartController::class, "remove"]);
    Route::delete("/cart", [CartController::class, "clear"]);
});
Route::get('/{slug}', [HomeController::class, 'productDetail']);




