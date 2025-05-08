<?php

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\WithdrawController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\Admin\ManagementController;
use App\Http\Controllers\Api\Admin\HomeController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\NotificationAdminController;

Route::prefix('admin')->group(function () {
    Route::post('/forget-password', [AdminAuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [AdminAuthController::class, 'changePassword']);
    Route::post('/verify-otp', [AdminAuthController::class, 'verifyOtp']);
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware([EnsureAdmin::class])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::put('/profile', [AdminAuthController::class, 'updateProfile']);
        Route::get('/profile', [AdminAuthController::class, 'profile']);
        Route::get('/index', [HomeController::class, 'index']);
        Route::post('management/index', [ManagementController::class, 'index']);
        Route::get('management/show/user/{user}', [ManagementController::class, 'showUser']);
        Route::get('management/show/driver/{driver}', [ManagementController::class, 'showDriver']);
        Route::get('management/show/order/{order}', [ManagementController::class, 'showOrder']);
        Route::delete('management/delete/{driver}', [ManagementController::class, 'deleteDriver']);


        Route::put('management/update/driver/{driver}', [ManagementController::class, 'updateDriver']);

        Route::apiResource('/banners', BannerController::class);



        Route::get('/notifications', [NotificationAdminController::class, 'index']);
        Route::get('/notifications/{notification}', [NotificationAdminController::class, 'show']);
        Route::delete('/notifications/{notification}', [NotificationAdminController::class, 'destroy']);
        // ----------------Notification-----------------------------------

        Route::put('/withdraws/{withdraw}', [WithdrawController::class, 'updateWithdrawStatus']);
        Route::get('/withdraws', [WithdrawController::class, 'AllWithdraws']);

        // ----------------Withdraw-----------------------------------


        Route::get('report/orders', [ReportController::class, 'orders']);



        Route::get('/complaints', [ComplaintController::class, 'index']);
        Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
        Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy']);
        Route::put('/complaints/{complaint}', [ComplaintController::class, 'reply']);
        //------------------------Complaint----------------------------

        Route::apiResource('/areas', AreaController::class);

    });
    Route::apiResource('countries', CountryController::class);
    // Route::post('management/index', [ManagementController::class, 'index']);
    // Route::get('management/show/user/{user}', [ManagementController::class, 'showUser']);
    //  Route::get('management/show/driver/{driver}', [ManagementController::class, 'showDriver']);
    // Route::get('management/show/order/{order}', [ManagementController::class, 'showOrder']);
    // Route::put('management/update/driver/{driver}', [ManagementController::class, 'updateDriver']);
    // Route::put('/withdraws/{withdraw}', [WithdrawController::class, 'updateWithdrawStatus']);
    //  Route::get('/withdraws', [WithdrawController::class, 'AllWithdraws']);

    //     Route::get('/complaints', [ComplaintController::class, 'index']);
    //  Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
    //     Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy']);
    //     Route::put('/complaints/{complaint}', [ComplaintController::class, 'reply']);
    Route::get('report/orders/export', [ReportController::class, 'exportOrdersInDay']);
    Route::get('report/drivers', [ReportController::class, 'drivers']);
    Route::get('report/drivers/export', [ReportController::class, 'exportDrivers']);

});