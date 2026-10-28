# P0 route baseline

Generated from the Laravel route collection in database-disabled testing mode. No endpoint is called.

A public method existing does not prove the endpoint works end-to-end. Closure bodies and provider integrations are not executed.

| Methods | URI | Action | Declared middleware | Handler |
| --- | --- | --- | --- | --- |
| GET, HEAD | `sanctum/csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` | `web` | Method exists |
| GET, HEAD | `_ignition/health-check` | `Spatie\LaravelIgnition\Http\Controllers\HealthCheckController` | `Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled` | Method exists |
| POST | `_ignition/execute-solution` | `Spatie\LaravelIgnition\Http\Controllers\ExecuteSolutionController` | `Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled` | Method exists |
| POST | `_ignition/update-config` | `Spatie\LaravelIgnition\Http\Controllers\UpdateConfigController` | `Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled` | Method exists |
| POST | `api/register` | `App\Http\Controllers\Api\AuthController@register` | `api` | Method exists |
| POST | `api/login` | `App\Http\Controllers\Api\AuthController@login` | `api` | Method exists |
| GET, HEAD | `api/auth/google/redirect` | `App\Http\Controllers\Api\GoogleAuthController@redirect` | `api` | Method exists |
| GET, HEAD | `api/auth/google/callback` | `App\Http\Controllers\Api\GoogleAuthController@callback` | `api` | Method exists |
| GET, HEAD | `api/provinces` | `Closure` | `api` | Closure |
| GET, HEAD | `api/districts/{id}` | `Closure` | `api` | Closure |
| GET, HEAD | `api/wards/{id}` | `Closure` | `api` | Closure |
| GET, HEAD | `api/vnpay/return` | `App\Http\Controllers\Api\CheckoutController@vnpReturn` | `api` | Method exists |
| POST | `api/logout` | `App\Http\Controllers\Api\AuthController@logout` | `api, auth:sanctum` | Method exists |
| GET, HEAD | `api/user` | `App\Http\Controllers\Api\UserController@profile` | `api, auth:sanctum` | Method exists |
| GET, HEAD | `api/categories` | `App\Http\Controllers\Api\CategoryController@index` | `api` | Method exists |
| POST | `api/categories` | `App\Http\Controllers\Api\CategoryController@store` | `api` | MISSING |
| GET, HEAD | `api/categories/{category}` | `App\Http\Controllers\Api\CategoryController@show` | `api` | MISSING |
| PUT, PATCH | `api/categories/{category}` | `App\Http\Controllers\Api\CategoryController@update` | `api` | MISSING |
| DELETE | `api/categories/{category}` | `App\Http\Controllers\Api\CategoryController@destroy` | `api` | MISSING |
| GET, HEAD | `api/promotions` | `App\Http\Controllers\Api\PromotionController@index` | `api` | Method exists |
| POST | `api/promotions` | `App\Http\Controllers\Api\PromotionController@store` | `api` | MISSING |
| GET, HEAD | `api/promotions/{promotion}` | `App\Http\Controllers\Api\PromotionController@show` | `api` | MISSING |
| PUT, PATCH | `api/promotions/{promotion}` | `App\Http\Controllers\Api\PromotionController@update` | `api` | MISSING |
| DELETE | `api/promotions/{promotion}` | `App\Http\Controllers\Api\PromotionController@destroy` | `api` | MISSING |
| GET, HEAD | `api/promotion-categories` | `App\Http\Controllers\Api\PromotionController@promotionCategories` | `api` | Method exists |
| GET, HEAD | `api/home` | `App\Http\Controllers\Api\HomeController@homeProducts` | `api` | Method exists |
| GET, HEAD | `api/home-main` | `App\Http\Controllers\Api\HomeController@homeMain` | `api` | Method exists |
| GET, HEAD | `api/banners` | `App\Http\Controllers\Api\BannerController@index` | `api` | Method exists |
| GET, HEAD | `api/blog` | `App\Http\Controllers\Api\HomeController@getBlogFeature` | `api` | MISSING |
| GET, HEAD | `api/cart` | `App\Http\Controllers\Api\CartController@getCart` | `api, auth:sanctum` | Method exists |
| POST | `api/cart/add` | `App\Http\Controllers\Api\CartController@add` | `api, auth:sanctum` | Method exists |
| PUT | `api/cart/item/{item}` | `App\Http\Controllers\Api\CartController@update` | `api, auth:sanctum` | Method exists |
| DELETE | `api/cart/item/{item}` | `App\Http\Controllers\Api\CartController@remove` | `api, auth:sanctum` | Method exists |
| DELETE | `api/cart` | `App\Http\Controllers\Api\CartController@clear` | `api, auth:sanctum` | Method exists |
| POST | `api/checkout` | `App\Http\Controllers\Api\CheckoutController@process` | `api, auth:sanctum` | Method exists |
| GET, HEAD | `api/wishlist` | `App\Http\Controllers\Api\WishlistController@index` | `api, auth:sanctum` | Method exists |
| POST | `api/wishlist/toggle` | `App\Http\Controllers\Api\WishlistController@toggle` | `api, auth:sanctum` | Method exists |
| DELETE | `api/wishlist/{id}` | `App\Http\Controllers\Api\WishlistController@destroy` | `api, auth:sanctum` | Method exists |
| GET, HEAD | `api/orders` | `App\Http\Controllers\Api\OrderController@index` | `api, auth:sanctum` | Method exists |
| GET, HEAD | `api/orders/{id}` | `App\Http\Controllers\Api\OrderController@show` | `api, auth:sanctum` | Method exists |
| GET, HEAD | `api/products/{slug}` | `App\Http\Controllers\Api\HomeController@productDetail` | `api` | Method exists |
| GET, HEAD | `api/products/{product}/variants` | `Closure` | `api` | Closure |
| GET, HEAD | `up` | `Closure` | `` | Closure |
| GET, HEAD | `login` | `App\Http\Controllers\AuthController@showLoginForm` | `web` | Method exists |
| POST | `login` | `App\Http\Controllers\AuthController@login` | `web` | Method exists |
| POST | `logout` | `App\Http\Controllers\AuthController@logout` | `web` | Method exists |
| GET, HEAD | `admin` | `App\Http\Controllers\DashboardController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/categories` | `App\Http\Controllers\Admin\CategoryController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/categories/trash` | `App\Http\Controllers\Admin\CategoryController@trash` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/categories/create` | `App\Http\Controllers\Admin\CategoryController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/categories/{id}` | `App\Http\Controllers\Admin\CategoryController@show` | `web, auth, is_admin` | Method exists |
| POST | `admin/categories/store` | `App\Http\Controllers\Admin\CategoryController@store` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/categories/edit/{id}` | `App\Http\Controllers\Admin\CategoryController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/categories/{id}` | `App\Http\Controllers\Admin\CategoryController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/categories/{id}` | `App\Http\Controllers\Admin\CategoryController@destroy` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/categories/{id}/restore` | `App\Http\Controllers\Admin\CategoryController@restore` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/categories/{id}/force-delete` | `App\Http\Controllers\Admin\CategoryController@forceDelete` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/products` | `App\Http\Controllers\Admin\ProductController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/products/trash` | `App\Http\Controllers\Admin\ProductController@trash` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/products/create` | `App\Http\Controllers\Admin\ProductController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/products/{id}` | `App\Http\Controllers\Admin\ProductController@show` | `web, auth, is_admin` | Method exists |
| POST | `admin/products/store` | `App\Http\Controllers\Admin\ProductController@store` | `web, auth, is_admin` | Method exists |
| POST | `admin/products/upload` | `App\Http\Controllers\Admin\ProductController@uploadTemp` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/products/edit/{id}` | `App\Http\Controllers\Admin\ProductController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/products/{id}` | `App\Http\Controllers\Admin\ProductController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/products/{id}` | `App\Http\Controllers\Admin\ProductController@destroy` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/products/{id}/restore` | `App\Http\Controllers\Admin\ProductController@restore` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/products/{id}/force-delete` | `App\Http\Controllers\Admin\ProductController@forceDelete` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product_variants` | `App\Http\Controllers\Admin\ProductVariantController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product_variants/trash` | `App\Http\Controllers\Admin\ProductVariantController@trash` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product_variants/create` | `App\Http\Controllers\Admin\ProductVariantController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product_variants/{id}` | `App\Http\Controllers\Admin\ProductVariantController@show` | `web, auth, is_admin` | Method exists |
| POST | `admin/product_variants/store` | `App\Http\Controllers\Admin\ProductVariantController@store` | `web, auth, is_admin` | Method exists |
| POST | `admin/product_variants/upload` | `App\Http\Controllers\Admin\ProductVariantController@uploadTemp` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/product_variants/edit/{id}` | `App\Http\Controllers\Admin\ProductVariantController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/product_variants/{id}` | `App\Http\Controllers\Admin\ProductVariantController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/product_variants/{id}` | `App\Http\Controllers\Admin\ProductVariantController@destroy` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/product_variants/{id}/restore` | `App\Http\Controllers\Admin\ProductVariantController@restore` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/product_variants/{id}/force-delete` | `App\Http\Controllers\Admin\ProductVariantController@forceDelete` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/dashboard` | `App\Http\Controllers\Admin\InventoryController@dashboard` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory` | `App\Http\Controllers\Admin\InventoryController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/import` | `App\Http\Controllers\Admin\InventoryController@importForm` | `web, auth, is_admin` | Method exists |
| POST | `admin/inventory/import` | `App\Http\Controllers\Admin\InventoryController@import` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/export` | `App\Http\Controllers\Admin\InventoryController@exportForm` | `web, auth, is_admin` | Method exists |
| POST | `admin/inventory/export` | `App\Http\Controllers\Admin\InventoryController@export` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/transfer` | `App\Http\Controllers\Admin\InventoryController@transferForm` | `web, auth, is_admin` | Method exists |
| POST | `admin/inventory/transfer` | `App\Http\Controllers\Admin\InventoryController@transfer` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/stocktake` | `App\Http\Controllers\Admin\InventoryController@stocktake` | `web, auth, is_admin` | Method exists |
| POST | `admin/inventory/stocktake` | `App\Http\Controllers\Admin\InventoryController@processStocktake` | `web, auth, is_admin` | Method exists |
| POST | `admin/inventory/adjust` | `App\Http\Controllers\Admin\InventoryController@adjust` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/transactions` | `App\Http\Controllers\Admin\InventoryController@transactions` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/transactions/{id}` | `App\Http\Controllers\Admin\InventoryController@transactionDetail` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/inventory/reports` | `App\Http\Controllers\Admin\InventoryController@reports` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/reports/export` | `App\Http\Controllers\Admin\InventoryController@exportExcel` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/settings` | `App\Http\Controllers\Admin\InventoryController@settings` | `web, auth, is_admin` | Method exists |
| POST | `admin/inventory/settings` | `App\Http\Controllers\Admin\InventoryController@updateSettings` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/inventory/api/warehouse/{id}/inventory` | `App\Http\Controllers\Admin\InventoryController@getWarehouseInventory` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/warehouses` | `App\Http\Controllers\Admin\WarehouseController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/warehouses/{warehouse}` | `App\Http\Controllers\Admin\WarehouseController@show` | `web, auth, is_admin` | MISSING |
| POST | `admin/warehouses/store` | `App\Http\Controllers\Admin\WarehouseController@store` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/warehouses/edit/{warehouse}` | `App\Http\Controllers\Admin\WarehouseController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/warehouses/{warehouse}` | `App\Http\Controllers\Admin\WarehouseController@update` | `web, auth, is_admin` | Method exists |
| POST | `admin/warehouses/search` | `App\Http\Controllers\Admin\WarehouseController@search` | `web, auth, is_admin` | Method exists |
| POST | `admin/warehouses/{id}/toggle-active` | `App\Http\Controllers\Admin\WarehouseController@toggleActive` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product-attributes` | `App\Http\Controllers\Admin\ProductAttributeController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product-attributes/create` | `App\Http\Controllers\Admin\ProductAttributeController@create` | `web, auth, is_admin` | Method exists |
| POST | `admin/product-attributes` | `App\Http\Controllers\Admin\ProductAttributeController@store` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/product-attributes/{product_attribute}` | `App\Http\Controllers\Admin\ProductAttributeController@show` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/product-attributes/{product_attribute}/edit` | `App\Http\Controllers\Admin\ProductAttributeController@edit` | `web, auth, is_admin` | Method exists |
| PUT, PATCH | `admin/product-attributes/{product_attribute}` | `App\Http\Controllers\Admin\ProductAttributeController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/product-attributes/{product_attribute}` | `App\Http\Controllers\Admin\ProductAttributeController@destroy` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/users` | `App\Http\Controllers\Admin\UserController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/users/trash` | `App\Http\Controllers\Admin\UserController@trash` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/users/create` | `App\Http\Controllers\Admin\UserController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/users/{user}` | `App\Http\Controllers\Admin\UserController@show` | `web, auth, is_admin` | Method exists |
| POST | `admin/users/store` | `App\Http\Controllers\Admin\UserController@store` | `web, auth, is_admin` | Method exists |
| POST | `admin/users/filter` | `App\Http\Controllers\Admin\UserController@filter` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/users/edit/{user}` | `App\Http\Controllers\Admin\UserController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/users/{user}` | `App\Http\Controllers\Admin\UserController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/users/{user}` | `App\Http\Controllers\Admin\UserController@destroy` | `web, auth, is_admin` | Method exists |
| PUT | `admin/users/updateEmailVerified/{user}` | `App\Http\Controllers\Admin\UserController@updateEmailVerified` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/users/{id}/restore` | `App\Http\Controllers\Admin\UserController@restore` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/users/{id}/force-delete` | `App\Http\Controllers\Admin\UserController@forceDelete` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/promotions` | `App\Http\Controllers\Admin\PromotionController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/promotions/trash` | `App\Http\Controllers\Admin\PromotionController@trash` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/promotions/create` | `App\Http\Controllers\Admin\PromotionController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/promotions/{promotion}` | `App\Http\Controllers\Admin\PromotionController@show` | `web, auth, is_admin` | MISSING |
| POST | `admin/promotions/store` | `App\Http\Controllers\Admin\PromotionController@store` | `web, auth, is_admin` | Method exists |
| POST | `admin/promotions/filter` | `App\Http\Controllers\Admin\PromotionController@filter` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/promotions/edit/{promotion}` | `App\Http\Controllers\Admin\PromotionController@edit` | `web, auth, is_admin` | MISSING |
| PUT | `admin/promotions/{promotion}` | `App\Http\Controllers\Admin\PromotionController@update` | `web, auth, is_admin` | MISSING |
| DELETE | `admin/promotions/{promotion}` | `App\Http\Controllers\Admin\PromotionController@destroy` | `web, auth, is_admin` | MISSING |
| PATCH | `admin/promotions/{id}/restore` | `App\Http\Controllers\Admin\PromotionController@restore` | `web, auth, is_admin` | MISSING |
| DELETE | `admin/promotions/{id}/force-delete` | `App\Http\Controllers\Admin\PromotionController@forceDelete` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/banners` | `App\Http\Controllers\Admin\BannerController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/banners/trash` | `App\Http\Controllers\Admin\BannerController@trash` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/banners/create` | `App\Http\Controllers\Admin\BannerController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/banners/{banner}` | `App\Http\Controllers\Admin\BannerController@show` | `web, auth, is_admin` | Method exists |
| POST | `admin/banners/store` | `App\Http\Controllers\Admin\BannerController@store` | `web, auth, is_admin` | Method exists |
| POST | `admin/banners/update-order` | `App\Http\Controllers\Admin\BannerController@updateOrder` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/banners/edit/{banner}` | `App\Http\Controllers\Admin\BannerController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/banners/{banner}` | `App\Http\Controllers\Admin\BannerController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/banners/{banner}` | `App\Http\Controllers\Admin\BannerController@destroy` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/banners/{id}/restore` | `App\Http\Controllers\Admin\BannerController@restore` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/banners/{id}/force-delete` | `App\Http\Controllers\Admin\BannerController@forceDelete` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/posts` | `App\Http\Controllers\Admin\PostController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/posts/trash` | `App\Http\Controllers\Admin\PostController@trash` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/posts/create` | `App\Http\Controllers\Admin\PostController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/posts/{post}` | `App\Http\Controllers\Admin\PostController@show` | `web, auth, is_admin` | Method exists |
| POST | `admin/posts/store` | `App\Http\Controllers\Admin\PostController@store` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/posts/edit/{post}` | `App\Http\Controllers\Admin\PostController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/posts/{post}` | `App\Http\Controllers\Admin\PostController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/posts/{post}` | `App\Http\Controllers\Admin\PostController@destroy` | `web, auth, is_admin` | MISSING |
| PATCH | `admin/posts/{id}/restore` | `App\Http\Controllers\Admin\PostController@restore` | `web, auth, is_admin` | MISSING |
| DELETE | `admin/posts/{id}/force-delete` | `App\Http\Controllers\Admin\PostController@forceDelete` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/frames` | `App\Http\Controllers\Admin\FrameController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/frames/trash` | `App\Http\Controllers\Admin\FrameController@trash` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/frames/create` | `App\Http\Controllers\Admin\FrameController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/frames/{frame}` | `App\Http\Controllers\Admin\FrameController@show` | `web, auth, is_admin` | MISSING |
| POST | `admin/frames/store` | `App\Http\Controllers\Admin\FrameController@store` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/frames/edit/{frame}` | `App\Http\Controllers\Admin\FrameController@edit` | `web, auth, is_admin` | MISSING |
| PUT | `admin/frames/{frame}` | `App\Http\Controllers\Admin\FrameController@update` | `web, auth, is_admin` | MISSING |
| DELETE | `admin/frames/{frame}` | `App\Http\Controllers\Admin\FrameController@destroy` | `web, auth, is_admin` | MISSING |
| PATCH | `admin/frames/{id}/restore` | `App\Http\Controllers\Admin\FrameController@restore` | `web, auth, is_admin` | MISSING |
| DELETE | `admin/frames/{id}/force-delete` | `App\Http\Controllers\Admin\FrameController@forceDelete` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/reviews` | `App\Http\Controllers\Admin\ReviewController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/reviews/{review}` | `App\Http\Controllers\Admin\ReviewController@show` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/reviews/{id}/toggle-status` | `App\Http\Controllers\Admin\ReviewController@toggleStatus` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/orders` | `App\Http\Controllers\Admin\OrderController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/orders/{order}` | `App\Http\Controllers\Admin\OrderController@show` | `web, auth, is_admin` | Method exists |
| PATCH | `admin/orders/{order}` | `App\Http\Controllers\Admin\OrderController@updateStatus` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/coupons` | `App\Http\Controllers\Admin\CouponController@index` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/coupons/exportCoupon` | `App\Http\Controllers\Admin\CouponController@exportCoupon` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/coupons/user-search` | `App\Http\Controllers\Admin\CouponController@couponUserSearch` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/coupons/create` | `App\Http\Controllers\Admin\CouponController@create` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/coupons/suggest-coupon-code` | `App\Http\Controllers\Admin\CouponController@suggestionCounpoun` | `web, auth, is_admin` | Method exists |
| POST | `admin/coupons` | `App\Http\Controllers\Admin\CouponController@store` | `web, auth, is_admin` | Method exists |
| POST | `admin/coupons/import` | `App\Http\Controllers\Admin\CouponController@importFile` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/coupons/deleted` | `App\Http\Controllers\Admin\CouponController@listDeleted` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `admin/coupons/{id}` | `App\Http\Controllers\Admin\CouponController@show` | `web, auth, is_admin` | Method exists |
| GET, HEAD | `admin/coupons/edit/{coupon}` | `App\Http\Controllers\Admin\CouponController@edit` | `web, auth, is_admin` | Method exists |
| PUT | `admin/coupons/{coupon}` | `App\Http\Controllers\Admin\CouponController@update` | `web, auth, is_admin` | Method exists |
| DELETE | `admin/coupons/{coupon}` | `App\Http\Controllers\Admin\CouponController@destroy` | `web, auth, is_admin` | MISSING |
| PUT | `admin/coupons/{coupon}/restore-delete` | `App\Http\Controllers\Admin\CouponController@restoreDelete` | `web, auth, is_admin` | MISSING |
| DELETE | `admin/coupons/{coupon}/force-delete` | `App\Http\Controllers\Admin\CouponController@forceDelete` | `web, auth, is_admin` | MISSING |
| GET, HEAD | `vnpay/return` | `App\Http\Controllers\Api\CheckoutController@vnpReturn` | `web` | Method exists |
| GET, HEAD | `/` | `Closure` | `web` | Closure |
| GET, HEAD | `{any}` | `Closure` | `web` | Closure |

Total route entries: 188. Missing controller methods: 40.

## Missing handlers

- `POST api/categories -> App\Http\Controllers\Api\CategoryController@store`
- `GET/HEAD api/categories/{category} -> App\Http\Controllers\Api\CategoryController@show`
- `PUT/PATCH api/categories/{category} -> App\Http\Controllers\Api\CategoryController@update`
- `DELETE api/categories/{category} -> App\Http\Controllers\Api\CategoryController@destroy`
- `POST api/promotions -> App\Http\Controllers\Api\PromotionController@store`
- `GET/HEAD api/promotions/{promotion} -> App\Http\Controllers\Api\PromotionController@show`
- `PUT/PATCH api/promotions/{promotion} -> App\Http\Controllers\Api\PromotionController@update`
- `DELETE api/promotions/{promotion} -> App\Http\Controllers\Api\PromotionController@destroy`
- `GET/HEAD api/blog -> App\Http\Controllers\Api\HomeController@getBlogFeature`
- `POST admin/products/upload -> App\Http\Controllers\Admin\ProductController@uploadTemp`
- `POST admin/product_variants/upload -> App\Http\Controllers\Admin\ProductVariantController@uploadTemp`
- `GET/HEAD admin/inventory/transactions/{id} -> App\Http\Controllers\Admin\InventoryController@transactionDetail`
- `GET/HEAD admin/warehouses/{warehouse} -> App\Http\Controllers\Admin\WarehouseController@show`
- `GET/HEAD admin/product-attributes/{product_attribute} -> App\Http\Controllers\Admin\ProductAttributeController@show`
- `POST admin/users/filter -> App\Http\Controllers\Admin\UserController@filter`
- `GET/HEAD admin/promotions/trash -> App\Http\Controllers\Admin\PromotionController@trash`
- `GET/HEAD admin/promotions/{promotion} -> App\Http\Controllers\Admin\PromotionController@show`
- `POST admin/promotions/filter -> App\Http\Controllers\Admin\PromotionController@filter`
- `GET/HEAD admin/promotions/edit/{promotion} -> App\Http\Controllers\Admin\PromotionController@edit`
- `PUT admin/promotions/{promotion} -> App\Http\Controllers\Admin\PromotionController@update`
- `DELETE admin/promotions/{promotion} -> App\Http\Controllers\Admin\PromotionController@destroy`
- `PATCH admin/promotions/{id}/restore -> App\Http\Controllers\Admin\PromotionController@restore`
- `DELETE admin/promotions/{id}/force-delete -> App\Http\Controllers\Admin\PromotionController@forceDelete`
- `GET/HEAD admin/posts/trash -> App\Http\Controllers\Admin\PostController@trash`
- `DELETE admin/posts/{post} -> App\Http\Controllers\Admin\PostController@destroy`
- `PATCH admin/posts/{id}/restore -> App\Http\Controllers\Admin\PostController@restore`
- `DELETE admin/posts/{id}/force-delete -> App\Http\Controllers\Admin\PostController@forceDelete`
- `GET/HEAD admin/frames/trash -> App\Http\Controllers\Admin\FrameController@trash`
- `GET/HEAD admin/frames/{frame} -> App\Http\Controllers\Admin\FrameController@show`
- `GET/HEAD admin/frames/edit/{frame} -> App\Http\Controllers\Admin\FrameController@edit`
- `PUT admin/frames/{frame} -> App\Http\Controllers\Admin\FrameController@update`
- `DELETE admin/frames/{frame} -> App\Http\Controllers\Admin\FrameController@destroy`
- `PATCH admin/frames/{id}/restore -> App\Http\Controllers\Admin\FrameController@restore`
- `DELETE admin/frames/{id}/force-delete -> App\Http\Controllers\Admin\FrameController@forceDelete`
- `GET/HEAD admin/coupons/exportCoupon -> App\Http\Controllers\Admin\CouponController@exportCoupon`
- `POST admin/coupons/import -> App\Http\Controllers\Admin\CouponController@importFile`
- `GET/HEAD admin/coupons/deleted -> App\Http\Controllers\Admin\CouponController@listDeleted`
- `DELETE admin/coupons/{coupon} -> App\Http\Controllers\Admin\CouponController@destroy`
- `PUT admin/coupons/{coupon}/restore-delete -> App\Http\Controllers\Admin\CouponController@restoreDelete`
- `DELETE admin/coupons/{coupon}/force-delete -> App\Http\Controllers\Admin\CouponController@forceDelete`
