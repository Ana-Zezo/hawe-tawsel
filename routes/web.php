<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FatoorahPaymentController;
use Illuminate\Support\Facades\Artisan;



Route::get('/', function () {
    return view('welcome');
});
// Route::get('orders-callback', function () {
//     return view('payment.success');
// });
Route::get('/wallet/recharge-success', [FatoorahPaymentController::class, 'walletRechargeSuccess']);
Route::get('orders-callback', [FatoorahPaymentController::class, 'success'])->name('orders.callback');

Route::get('orders-error', [FatoorahPaymentController::class, 'error'])->name('orders.error');


Route::get('/test', function () {
    // Artisan::call('storage:link');
        // Artisan::call('db:seed --class=AdminSeeder');
        // Artisan::call('make:migration add_country_id_to_withdraws_table');
        // Artisan::call('make:migration add_country_id_to_complains_table');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('optimize:clear');
        Artisan::call('route:clear');
        dd(Artisan::call('route:list'));
        // exec('composer require maatwebsite/excel:^3.1');

});


// "https://b2f5-197-38-174-168.ngrok-free.app/orders-callback";