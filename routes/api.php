<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CashierShifts\CashierShiftController;
use App\Http\Controllers\Api\V1\Customers\CustomerController;
use App\Http\Controllers\Api\V1\ServiceOrders\InvoiceController;
use App\Http\Controllers\Api\V1\ServiceOrders\PaymentController;
use App\Http\Controllers\Api\V1\ServiceOrders\ServiceOrderController;
use App\Http\Controllers\Api\V1\ServiceOrders\ShoePhotoController;
use App\Http\Controllers\Api\V1\Services\PaymentMethodController;
use App\Http\Controllers\Api\V1\Services\ServiceCatalogController;
use App\Http\Controllers\Api\V1\TechnicianWork\TechnicianWorkController;
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
    Route::post('service-orders/{order}/pickup', [ServiceOrderController::class, 'pickup'])
        ->middleware('permission:service_orders.pickup');

    // Payment (kasir/admin/owner; void/refund admin+owner).
    Route::get('service-orders/{order}/payments', [PaymentController::class, 'index'])
        ->middleware('permission:payments.view');
    Route::post('service-orders/{order}/payments', [PaymentController::class, 'store'])
        ->middleware('permission:payments.create');
    Route::post('service-orders/{order}/payments/{payment}/void', [PaymentController::class, 'void'])
        ->middleware('permission:payments.void');
    Route::post('service-orders/{order}/payments/{payment}/refund', [PaymentController::class, 'refund'])
        ->middleware('permission:payments.refund');

    // Invoice (kasir/admin/owner).
    Route::get('service-orders/{order}/invoice', [InvoiceController::class, 'show'])
        ->middleware('permission:invoices.view');
    Route::get('service-orders/{order}/invoice/pdf', [InvoiceController::class, 'pdf'])
        ->middleware('permission:invoices.view');
    Route::post('service-orders/{order}/invoice/send', [InvoiceController::class, 'markSent'])
        ->middleware('permission:invoices.send');

    // Shoe photos (work.photos / service_orders.view).
    Route::get('service-orders/{order}/photos', [ShoePhotoController::class, 'index'])
        ->middleware('permission:service_orders.view');
    Route::post('service-orders/{order}/shoes/{shoe}/photos', [ShoePhotoController::class, 'store'])
        ->middleware('permission:work.photos');
    Route::get('shoe-photos/{photo}/file', [ShoePhotoController::class, 'file'])
        ->middleware('permission:service_orders.view');

    // Payment methods (kasir perlu daftar saat menerima pembayaran).
    Route::get('payment-methods', [PaymentMethodController::class, 'index'])
        ->middleware('permission:payments.view');

    // Cashier shift (kasir/admin/owner).

    // Technician work queue & item workflow (Phase 5).
    Route::get('work/queue', [TechnicianWorkController::class, 'queue'])
        ->middleware('permission:work.view');
    Route::post('work/items/{item}/assign', [TechnicianWorkController::class, 'assign'])
        ->middleware('permission:service_orders.assign');
    Route::post('work/items/{item}/status', [TechnicianWorkController::class, 'changeStatus'])
        ->middleware('permission:work.item_status');
    Route::post('work/items/{item}/notes', [TechnicianWorkController::class, 'addNote'])
        ->middleware('permission:work.notes');
    Route::get('cashier-shifts/current', [CashierShiftController::class, 'current'])
        ->middleware('permission:payments.view');
    Route::post('cashier-shifts', [CashierShiftController::class, 'store'])
        ->middleware('permission:payments.create');
    Route::post('cashier-shifts/{shift}/close', [CashierShiftController::class, 'close'])
        ->middleware('permission:payments.create');
});
