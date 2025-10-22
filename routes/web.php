<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductAttributeController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Route admin, có middleware kiểm tra role
Route::prefix('admin')->middleware(['auth', 'is_admin'])->as('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Route Categories
    Route::prefix('categories')->as('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/trash', [CategoryController::class, 'trash'])->name('trash');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
        Route::post('/store', [CategoryController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [CategoryController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [CategoryController::class, 'forceDelete'])->name('force-delete');
    });
    Route::prefix('products')->as('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/trash', [ProductController::class, 'trash'])->name('trash');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');
        Route::post('/store', [ProductController::class, 'store'])->name('store');
        Route::post('/upload', [ProductController::class, 'uploadTemp'])->name('upload');
        Route::get('/edit/{id}', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [ProductController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('force-delete');
    });
    Route::prefix('product_variants')->as('product_variants.')->group(function () {
        Route::get('/', [ProductVariantController::class, 'index'])->name('index');
        Route::get('/trash', [ProductVariantController::class, 'trash'])->name('trash');
        Route::get('/create', [ProductVariantController::class, 'create'])->name('create');
        Route::get('/{id}', [ProductVariantController::class, 'show'])->name('show');
        Route::post('/store', [ProductVariantController::class, 'store'])->name('store');
        Route::post('/upload', [ProductVariantController::class, 'uploadTemp'])->name('upload');
        Route::get('/edit/{id}', [ProductVariantController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductVariantController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductVariantController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [ProductVariantController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [ProductVariantController::class, 'forceDelete'])->name('force-delete');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        // Dashboard
        Route::get('dashboard', [InventoryController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [InventoryController::class, 'index'])->name('index');

        // Import
        Route::get('import', [InventoryController::class, 'importForm'])->name('import');
        Route::post('import', [InventoryController::class, 'import'])->name('import.store');

        // Export
        Route::get('export', [InventoryController::class, 'exportForm'])->name('export');
        Route::post('export', [InventoryController::class, 'export'])->name('export.store');

        // Transfer
        Route::get('transfer', [InventoryController::class, 'transferForm'])->name('transfer');
        Route::post('transfer', [InventoryController::class, 'transfer'])->name('transfer.store');

        // Stock take
        Route::get('stocktake', [InventoryController::class, 'stocktake'])->name('stocktake');
        Route::post('stocktake', [InventoryController::class, 'processStocktake'])->name('stocktake.process');

        // Adjust
        Route::post('adjust', [InventoryController::class, 'adjust'])->name('adjust');

        // Transactions
        Route::get('transactions', [InventoryController::class, 'transactions'])->name('transactions');
        Route::get('transactions/{id}', [InventoryController::class, 'transactionDetail'])->name('transactions.detail');

        // Reports
        Route::get('reports', [InventoryController::class, 'reports'])->name('reports');
        Route::get('reports/export', [InventoryController::class, 'exportExcel'])->name('reports.export');

        // Settings
        Route::get('settings', [InventoryController::class, 'settings'])->name('settings');
        Route::post('settings', [InventoryController::class, 'updateSettings'])->name('settings.update');

        // API endpoints
        Route::get('api/warehouse/{id}/inventory', [InventoryController::class, 'getWarehouseInventory'])
            ->name('api.warehouse.inventory');
    });

    // Warehouses
    Route::prefix('warehouses')->as('warehouses.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Route::post('/store', [WarehouseController::class, 'store'])->name('store');
        Route::get('/edit/{warehouse}', [WarehouseController::class, 'edit'])->name('edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::post('/search', [WarehouseController::class, 'search'])->name('search');
        Route::post('/{id}/toggle-active', [WarehouseController::class, 'toggleActive']);
    });
    Route::resource('product-attributes', ProductAttributeController::class);
    Route::prefix('users')->as('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/trash', [UserController::class, 'trash'])->name('trash');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::post('/filter', [UserController::class, 'filter'])->name('filter');
        Route::get('/edit/{user}', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::put('/updateEmailVerified/{user}', [UserController::class, 'updateEmailVerified'])->name('updateEmailVerified');
        Route::patch('/{id}/restore', [UserController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [UserController::class, 'forceDelete'])->name('force-delete');
    });
    Route::prefix('promotions')->as('promotions.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::get('/trash', [PromotionController::class, 'trash'])->name('trash');
        Route::get('/create', [PromotionController::class, 'create'])->name('create');
        Route::get('/{promotion}', [PromotionController::class, 'show'])->name('show');
        Route::post('/store', [PromotionController::class, 'store'])->name('store');
        Route::post('/filter', [PromotionController::class, 'filter'])->name('filter');
        Route::get('/edit/{promotion}', [PromotionController::class, 'edit'])->name('edit');
        Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [PromotionController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [PromotionController::class, 'forceDelete'])->name('force-delete');
    });
    Route::prefix('banners')->as('banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::get('/trash', [BannerController::class, 'trash'])->name('trash');
        Route::get('/create', [BannerController::class, 'create'])->name('create');
        Route::get('/{banner}', [BannerController::class, 'show'])->name('show');
        Route::post('/store', [BannerController::class, 'store'])->name('store');
        Route::post('/update-order', [BannerController::class, 'updateOrder'])->name('updateOrder');
        Route::get('/edit/{banner}', [BannerController::class, 'edit'])->name('edit');
        Route::put('/{banner}', [BannerController::class, 'update'])->name('update');
        Route::delete('/{banner}', [BannerController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [BannerController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [BannerController::class, 'forceDelete'])->name('force-delete');
    });
});
Route::get('/', function () {
    return view('clients.client');
});

Route::get('/{any}', function () {
    return view('clients.client'); // app.blade.php chứa <div id="app"></div>
})->where('any', '.*');
