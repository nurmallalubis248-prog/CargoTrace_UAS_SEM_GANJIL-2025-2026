<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ErpController;
use App\Http\Controllers\Api\ExceptionController;
use App\Http\Controllers\Api\ExternalApiController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\TrackingController;
use Illuminate\Support\Facades\Route;

// All routes below are prefixed with /api (see bootstrap/app.php) and named api.*
Route::name('api.')->group(function () {
    // ---- ERP integration (X-ERP-KEY header) ----------------------------
    Route::middleware(['erp.key', 'throttle:60,1'])->prefix('erp')->name('erp.')->group(function () {
        Route::get('shipments', [ErpController::class, 'shipments'])->name('shipments');
        Route::get('payments', [ErpController::class, 'payments'])->name('payments');
    });

    // ---- Authentication ------------------------------------------------
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');

        // ---- Shipments (authorization via ShipmentPolicy) ----------------
        Route::apiResource('shipments', ShipmentController::class);
        Route::get('shipments/{shipment}/tracking', [TrackingController::class, 'index'])->name('shipments.tracking.index');
        Route::post('shipments/{shipment}/tracking', [TrackingController::class, 'store'])->name('shipments.tracking.store');
        Route::get('shipments/{shipment}/exceptions', [ExceptionController::class, 'index'])->name('shipments.exceptions.index');
        Route::post('shipments/{shipment}/exceptions', [ExceptionController::class, 'store'])->name('shipments.exceptions.store');
        Route::patch('exceptions/{exception}', [ExceptionController::class, 'update'])->name('exceptions.update');
        Route::get('shipments/{shipment}/weather', [ExternalApiController::class, 'weather'])->name('shipments.weather');

        // ---- Dashboard & notifications -----------------------------------
        Route::get('dashboard', [DashboardController::class, 'stats'])->name('dashboard');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        // ---- Staff only (admin + operator) -------------------------------
        Route::middleware('role:admin,operator')->group(function () {
            Route::get('carriers', [CarrierController::class, 'index'])->name('carriers.index');
            Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('geocode', [ExternalApiController::class, 'geocode'])->middleware('throttle:20,1')->name('geocode');
        });

        // ---- Admin only --------------------------------------------------
        Route::middleware('role:admin')->group(function () {
            Route::post('carriers', [CarrierController::class, 'store'])->name('carriers.store');
            Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
        });
    });
});
