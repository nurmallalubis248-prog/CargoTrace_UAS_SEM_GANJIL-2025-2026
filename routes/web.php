<?php

use App\Http\Controllers\Api\DashboardController as DashboardStatsController;
use App\Http\Controllers\Api\ExternalApiController;
use App\Http\Controllers\Web\Admin\AuditLogController;
use App\Http\Controllers\Web\Admin\CarrierController;
use App\Http\Controllers\Web\Admin\LocationController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\WarehouseController;
use App\Http\Controllers\Web\Admin\AccountingController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExceptionController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\ShipmentController;
use App\Http\Controllers\Web\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Midtrans webhook: public (no session), signature-verified, CSRF-exempt (see bootstrap/app.php)
Route::post('midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/stats', [DashboardStatsController::class, 'stats'])->name('dashboard.stats');

    // Shipments (authorization via ShipmentPolicy inside the controller / form requests)
    Route::resource('shipments', ShipmentController::class);
    Route::post('shipments/{shipment}/tracking', [TrackingController::class, 'store'])->name('shipments.tracking.store');
    Route::post('shipments/{shipment}/exceptions', [ExceptionController::class, 'store'])->name('shipments.exceptions.store');
    Route::patch('exceptions/{exception}', [ExceptionController::class, 'update'])->name('exceptions.update');
    Route::get('shipments/{shipment}/weather', [ExternalApiController::class, 'weather'])->name('shipments.weather');
    Route::get('shipments/{shipment}/live', [ShipmentController::class, 'live'])->name('shipments.live');

    Route::post('shipments/{shipment}/pay', [PaymentController::class, 'store'])->middleware('throttle:10,1')->name('shipments.pay');
    Route::get('payments/{payment}/sync', [PaymentController::class, 'sync'])->name('payments.sync');
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::post('shipments/{shipment}/confirm', [ShipmentController::class, 'confirm'])->name('shipments.confirm');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/pdf', [ReportController::class, 'pdf'])->middleware('throttle:10,1')->name('reports.pdf');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');

    Route::middleware('role:admin,operator')->group(function () {
        Route::get('geocode', [ExternalApiController::class, 'geocode'])->middleware('throttle:20,1')->name('geocode');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('carriers', [CarrierController::class, 'index'])->name('carriers.index');
        Route::post('carriers', [CarrierController::class, 'store'])->name('carriers.store');
        Route::patch('carriers/{carrier}/toggle', [CarrierController::class, 'toggle'])->name('carriers.toggle');

        Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
        Route::patch('locations/{location}/toggle', [LocationController::class, 'toggle'])->name('locations.toggle');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');

        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::patch('warehouses/{warehouse}/toggle', [WarehouseController::class, 'toggle'])->name('warehouses.toggle');

        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');
        Route::get('accounting/export', [AccountingController::class, 'export'])->name('accounting.export');
        Route::get('accounting/pdf', [AccountingController::class, 'pdf'])->middleware('throttle:10,1')->name('accounting.pdf');
        Route::post('accounting/{payment}/settle', [AccountingController::class, 'settle'])->name('accounting.settle');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
