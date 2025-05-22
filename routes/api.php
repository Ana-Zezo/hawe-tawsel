<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Http\Request;
use App\Trait\OTPVerification;
use App\Http\Middleware\EnsureUser;
use Illuminate\Support\Facades\App;
use App\Http\Middleware\LogRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Notifications\NewOrderNotification;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SenderController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\HomeUserController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\FatoorahPaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Notifications\OrderStatusUpdatedNotification;


Route::fallback(function () {
    return response()->json([
        'status' => false,
        'message' => 'بتعمل ايه ي حمادة لقد طفح الكيل قوم ي حمادة'
    ], 404);
});


Route::post('/forget-password', [AuthController::class, 'forgetPassword'])->name('forget-password');
Route::post('/reset-password', [AuthController::class, 'changePassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOTP'])->name('verify-otp');
Route::post('/resend-code', [AuthController::class, 'resetCode']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/countries', [CountryController::class, 'index']);
Route::post('/check-coordinates', [OrderController::class, 'checkSingleCoordinate']);
//------------------------Auth Done----------------------------

Route::middleware([EnsureUser::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/home', [HomeUserController::class, 'index']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);


    Route::apiResource('orders', OrderController::class)->only('store', 'show');
    Route::get('orders-delete-receiver/{order}', [OrderController::class, 'deleteOrderReceiver']);
    Route::get('orders-delete-sender/{order}', [OrderController::class, 'deleteOrderSender']);
    Route::get('cancel-orders/{order}', [OrderController::class, 'cancelOrder']);
    Route::get('address-sender', [OrderController::class, 'addressSender']);
    Route::get('address-receiver', [OrderController::class, 'addressReceiver']);
    Route::get('myOrders', [OrderController::class, 'ordersUser']);
    Route::get('order-sender', [OrderController::class, 'orderSender']);
    Route::get('orders-receiver', [OrderController::class, 'orderReceiver']);
    Route::delete('orders-delete/{order}', [OrderController::class, 'orderDelete']);
    Route::put('/orders/rate', [OrderController::class, 'rate']);
    Route::get('/user/areas', [OrderController::class, 'getAreasForUserCountry']);
    Route::get('order-status/{order}', [OrderController::class, 'orderStatus']);


    //------------------------Order----------------------------
    Route::post('orders-payment/{order}', [FatoorahPaymentController::class, 'payOrder']);
    Route::post('/order-payment-wallet/{order}', [FatoorahPaymentController::class, 'payFromWallet']);
    Route::post('/wallet/recharge', [FatoorahPaymentController::class, 'rechargeWallet']);
    Route::get('/wallet/balance', [FatoorahPaymentController::class, 'getWalletBalance']);
    Route::get('/wallet', [FatoorahPaymentController::class, 'getAllWallets']);
    //------------------------Payment----------------------------

    Route::get('notification/index', [NotificationController::class, 'index']);
    Route::get('notification/show/{id}', [NotificationController::class, 'show']);
    Route::get('notification/unread', [NotificationController::class, 'unread']);
    //------------------------Notification----------------------------


    Route::post('/complaints', [ComplaintController::class, 'store']);
    //------------------------Complaint----------------------------
});

Route::post('/test-sms', function (OTPVerification $smsService) {
    $response = $smsService->sendOTP('580087671', '1234');
    return response()->json($response);
});