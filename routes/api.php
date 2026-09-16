<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public Auth Route
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected API Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Items CRUD
    Route::apiResource('items', ItemController::class);

    // Transactions log & statistics
    Route::get('/transactions/stats', [TransactionController::class, 'getStats']);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store']);

    // Stock Status & Stock Cards
    Route::get('/stock', [StockController::class, 'index']);
    Route::get('/stock/{item_id}/card', [StockController::class, 'getCard']);

    // Master Data Baru
    Route::apiResource('locations', LocationController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('vendors', VendorController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'destroy']);

    // Master Kategori (dengan update)
    Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

    // Manajemen Pengguna (Admin only)
    Route::post('/users/import', [UserController::class, 'import']);
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    // Serialized Assets (Sewa / Bekas)
    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::post('/assets/mutate', [AssetController::class, 'mutateLocation']);
    Route::post('/assets/rent-out', [AssetController::class, 'rentOut']);
    Route::post('/assets/rent-return', [AssetController::class, 'rentReturn']);
    Route::post('/assets/{id}/status', [AssetController::class, 'updateStatus']);
    Route::get('/assets/{id}/card', [AssetController::class, 'getAssetCard']);

    // MIKMS (Micro:bit Kit Management System)
    Route::prefix('mikms')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\MikmsController::class, 'getDashboard']);
        Route::get('/modules', [\App\Http\Controllers\Api\MikmsController::class, 'getModules']);
        Route::get('/boxes', [\App\Http\Controllers\Api\MikmsController::class, 'getBoxes']);
        Route::post('/boxes', [\App\Http\Controllers\Api\MikmsController::class, 'storeBox']);
        Route::post('/productions', [\App\Http\Controllers\Api\MikmsController::class, 'storeProduction']);
        Route::post('/qc-logs', [\App\Http\Controllers\Api\MikmsController::class, 'storeQcLog']);
        Route::post('/shipments', [\App\Http\Controllers\Api\MikmsController::class, 'storeShipment']);
        Route::post('/returns', [\App\Http\Controllers\Api\MikmsController::class, 'storeReturn']);
        Route::post('/repairs', [\App\Http\Controllers\Api\MikmsController::class, 'storeRepair']);
        Route::post('/stock-opnames', [\App\Http\Controllers\Api\MikmsController::class, 'storeStockOpname']);
        Route::get('/logs', [\App\Http\Controllers\Api\MikmsController::class, 'getLogs']);
        Route::get('/export/excel', [\App\Http\Controllers\Api\MikmsExportController::class, 'exportExcel']);

        // Smart Cascading BOM — Package Order
        Route::get('/package-simulate', [\App\Http\Controllers\Api\MikmsController::class, 'packageSimulate']);
        Route::get('/package-orders', [\App\Http\Controllers\Api\MikmsController::class, 'getPackageOrders']);
        Route::post('/package-orders', [\App\Http\Controllers\Api\MikmsController::class, 'storePackageOrder']);

        // Module Stock Management
        Route::get('/module-stocks', [\App\Http\Controllers\Api\MikmsController::class, 'getModuleStocks']);
        Route::post('/module-stocks/adjust', [\App\Http\Controllers\Api\MikmsController::class, 'adjustModuleStock']);
    });

    // File Downloads / Exports
    Route::get('/export/excel', [ExportController::class, 'exportExcel']);
    Route::get('/export/pdf/{item_id}', [ExportController::class, 'exportPdf']);
});
