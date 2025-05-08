<?php

use Illuminate\Http\Request;
use App\Http\Middleware\EnsureDriver;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\DriverAuthController;
use App\Http\Controllers\Api\WithdrawController;
use App\Http\Controllers\Api\NotificationController;



Route::prefix('driver')->group(function () {
    Route::post('/forget-password', [DriverAuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [DriverAuthController::class, 'changePassword']);
    Route::post('/verify-otp', [DriverAuthController::class, 'verifyOTP']);
    // Route::post('/confirm-password', [DriverAuthController::class, 'confirmRestPassword']);
    Route::post('/register', [DriverAuthController::class, 'register']);
    Route::post('/login', [DriverAuthController::class, 'login']);


    Route::middleware([EnsureDriver::class])->group(function () {
        Route::post('/logout', [DriverAuthController::class, 'logout']);
        Route::get('/profile', [DriverAuthController::class, 'profile']);
        Route::put('/profile', [DriverAuthController::class, 'updateProfile']);
        // ----------------Order-------------------------
        Route::get('/orders/created', [OrderController::class, 'createOrder']);
        Route::get('/orders/areas', [OrderController::class, 'areaDriver']);
        Route::get('/orders/{order}',[OrderController::class, 'showDetails']);
        
        Route::put('orders/{order}', [OrderController::class, 'update']);
        Route::get('/orders-by-status', [OrderController::class, 'getOrdersByStatus']);

        Route::get('/orders/cancel/{order}', [OrderController::class, 'cancelOrderByDriver']);


        Route::get('/withdraws', [WithdrawController::class, 'index']);
        Route::post('/withdraws', [WithdrawController::class, 'store']);
        // ----------------Withdraw-------------------------
        
         Route::get('notification/index', [NotificationController::class, 'index']);
        Route::get('notification/show/{id}', [NotificationController::class, 'show']);
        // ----------------Notification-------------------------
    });
});