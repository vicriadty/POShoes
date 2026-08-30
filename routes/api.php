<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customers\CustomerController;
use App\Http\Controllers\Api\V1\ServiceOrders\ServiceOrderController;
use App\Http\Controllers\Api\V1\Services\ServiceCatalogController;
use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('guest');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('me/permissions', [AuthController::class, 'permissions']);
    });
});

// Endpoint terproteksi akan ditambahkan per-modul (Phase 3+).

Route::middleware('auth:sanctum')->prefix('users')->group(function (): void {
    Route::get('/', [UserController::class, 'index'])
        ->middleware('permission:users.view');
    Route::post('/', [UserController::class, 'store'])
        ->middleware('permission:users.create');
});

Route::middleware('auth:sanctum')->group(function (): void {
    // Customer (roles-permissions: kasir/admin/owner).
    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view');
    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.update');

    // Service catalog (roles-permissions: admin/owner).
    Route::get('services', [ServiceCatalogController::class, 'index'])
        ->middleware('permission:services.view');
    Route::get('services/categories', [ServiceCatalogController::class, 'categories'])
        ->middleware('permission:services.view');
    Route::post('services/categories', [ServiceCatalogController::class, 'storeCategory'])
        ->middleware('permission:services.create');
    Route::post('services', [ServiceCatalogController::class, 'store'])
        ->middleware('permission:services.create');
    Route::get('services/{service}', [ServiceCatalogController::class, 'show'])
        ->middleware('permission:services.view');
    Route::put('services/{service}', [ServiceCatalogController::class, 'update'])
        ->middleware('permission:services.update');

    // Service order (kasir/admin/owner).
    Route::get('service-orders', [ServiceOrderController::class, 'index'])
        ->middleware('permission:service_orders.view');
    Route::post('service-orders', [ServiceOrderController::class, 'store'])
        ->middleware('permission:service_orders.create');
    Route::get('service-orders/{order}', [ServiceOrderController::class, 'show'])
        ->middleware('permission:service_orders.view');
    Route::post('service-orders/{order}/status', [ServiceOrderController::class, 'changeStatus'])
        ->middleware('permission:service_orders.change_status');
});
