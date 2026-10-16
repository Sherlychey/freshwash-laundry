<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PickupController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pesan', [OrderController::class, 'create'])->name('orders.create');
Route::post('/pesan', [OrderController::class, 'store'])->middleware('throttle:8,1')->name('orders.store');
Route::get('/pesanan/{orderCode}/konfirmasi', [OrderController::class, 'confirmation'])->name('orders.confirmation');
Route::get('/lacak', [OrderController::class, 'track'])->name('orders.track');
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.user'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('pelanggan', CustomerController::class)->except('show')->names('customers');
    Route::resource('layanan', ServiceController::class)->except('show')->names('services');
    Route::get('/pembayaran', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/pesanan/{order}/pembayaran', [PaymentController::class, 'store'])->name('payments.store');
    Route::patch('/pembayaran/{payment}/verifikasi', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('/antar-jemput', [PickupController::class, 'index'])->name('pickups.index');
    Route::patch('/antar-jemput/{order}', [PickupController::class, 'update'])->name('pickups.update');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/pesanan/{order}/nota', [ReportController::class, 'receipt'])->name('orders.receipt');
    Route::get('/pesanan', [OrderManagementController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderManagementController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/terima', [OrderManagementController::class, 'accept'])->name('orders.accept');
    Route::post('/pesanan/{order}/tolak', [OrderManagementController::class, 'reject'])->name('orders.reject');
    Route::patch('/pesanan/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/pesanan/{order}/timbang', [OrderManagementController::class, 'weigh'])->name('orders.weigh');
    Route::patch('/pesanan/{order}/konfirmasi-item', [OrderManagementController::class, 'confirmQuantity'])->name('orders.confirm-quantity');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

