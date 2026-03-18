<?php



use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\BannerController;

use App\Http\Controllers\Api\CartController;

use App\Http\Controllers\Api\CategoryController;

use App\Http\Controllers\Api\CheckoutController;

use App\Http\Controllers\Api\GoogleAuthController;

use App\Http\Controllers\Api\HomeController;

use App\Http\Controllers\Api\OrderController;

use App\Http\Controllers\Api\PromotionController;

use App\Http\Controllers\Api\UserController;

use App\Http\Controllers\Api\WishlistController;

use Illuminate\Support\Facades\Route;



// Auth routes

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/provinces', function () {

    return file_get_contents("https://provinces.open-api.vn/api/p/");

});

Route::get('/districts/{id}', function ($id) {

    return file_get_contents("https://provinces.open-api.vn/api/p/$id?depth=2");

});

Route::get('/wards/{id}', function ($id) {

    return file_get_contents("https://provinces.open-api.vn/api/d/$id?depth=2");

});

// Những route cần token mới truy cập

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [UserController::class, 'profile']);

});

Route::apiResource('/categories', CategoryController::class);

Route::apiResource('/promotions', PromotionController::class);

Route::get('/promotion-categories', [PromotionController::class, 'promotionCategories']);



Route::get('/home', [HomeController::class, 'homeProducts']);

Route::get('/home-main', [HomeController::class, 'homeMain']);

Route::get('/banners', [BannerController::class, 'index']);

Route::get('/blog', [HomeController::class, 'getBlogFeature']);

Route::middleware("auth:sanctum")->group(function () {

    Route::get("/cart", [CartController::class, "getCart"]);

    Route::post("/cart/add", [CartController::class, "add"]);

    Route::put("/cart/item/{item}", [CartController::class, "update"]);

    Route::delete("/cart/item/{item}", [CartController::class, "remove"]);

    Route::delete("/cart", [CartController::class, "clear"]);



    // Tạo đơn + Thanh toán (VNPay hoặc COD)

    Route::post('/checkout', [CheckoutController::class, 'process']);



    //Sản phẩm yêu thích

    Route::get('/wishlist', [WishlistController::class, 'index']);

    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);

    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);

    

    //Lịch sử đơn hàng

    Route::get('/orders', [OrderController::class, 'index']);      // danh sách + filter

    Route::get('/orders/{id}', [OrderController::class, 'show']);  // chi tiết đơn

});

Route::get('/products/{slug}', [HomeController::class, 'productDetail']);

Route::get('/products/{product}/variants', function ($productId) {

    return \App\Models\ProductVariant::where('product_id', $productId)

        ->get(['id', 'sku', 'color', 'storage', 'cost_price']);

});









