<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Wallet;
use App\Trait\ApiResponse;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\FatoorahServices;
use Illuminate\Support\Facades\DB;
use MyFatoorah\Library\MyFatoorah;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use App\Http\Resources\Transaction\TransactionResource;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentEmbedded;

class FatoorahPaymentController extends Controller
{
    public $mfConfig = [];
    public function __construct()
    {
        $this->mfConfig = [
            'apiKey' => env('MY_FATOORAH_API_TOKEN'),
            'isTest' => config('myfatoorah.test_mode'),
            'countryCode' => config('myfatoorah.country_iso'),
        ];
    }



    // public function payFromWallet(Order $order)
    // {
    //     $user = Auth::user();

    //     if (!$order) {
    //         return ApiResponse::errorResponse(false, 'Order not found.');
    //     }

    //     $existingTransaction = Transaction::where('user_id', $user->id)
    //         ->where('order_id', $order->id)
    //         ->where('status', 'success')
    //         ->first();

    //     if ($existingTransaction) {
    //         return ApiResponse::errorResponse(false, 'This order has already been paid.');
    //     }

    //     $walletBalance = $user->wallet;

    //     if ($walletBalance < $order->totalPrice) {
    //         return ApiResponse::errorResponse(false, 'Insufficient wallet balance.');
    //     }

    //     $transaction = Transaction::create([
    //         'paymentId' => null,
    //         'order_id' => $order->id,
    //         'user_id' => $user->id,
    //         'status' => 'pending',
    //         'type' => 'wallet',
    //         'amount' => $order->totalPrice,
    //     ]);


    //     $user->decrement('wallet', $order->totalPrice);

    //     $transaction->update(['status' => 'success']);

    //     return ApiResponse::sendResponse(true, 'Payment completed successfully from wallet.', $order);
    // }



    public function payOrder(Request $request, Order $order)
    {
        $type = $request->type;
        $user = Auth::user();

        if (!$order) {
            return ApiResponse::errorResponse(false, 'Order not found.');
        }

        if (!$user->first_name || !$user->last_name || !$user->phone) {
            return ApiResponse::errorResponse(false, 'User data is incomplete.');
        }

        $country = $user->country;
        if (!$country) {
            return ApiResponse::errorResponse(false, 'User country not found.');
        }
        $existingTransaction = Transaction::where('user_id', $user->id)
            ->where('order_id', $order->id)
            ->where('status', 'success')
            ->exists();

        if ($existingTransaction) {
            return ApiResponse::errorResponse(false, 'This order has already been paid.');
        }
         $fullName = "{$user->first_name} {$user->last_name}";
        DB::beginTransaction();

        try {
            if ($type === 'wallet') {
                $walletBalance = $user->wallet;
                if ($walletBalance < $order->totalPrice) {
                    return ApiResponse::errorResponse(false, 'Insufficient wallet balance.');
                }
                Wallet::create([
                    'user_id' => $user->id,
                    'type' => 'paidOrder',
                    'amount' => $order->totalPrice,
                    'status' => 'success',
                ]);
                $transaction = Transaction::create([
                    'paymentId' => null,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'status' => 'success',
                    'type' => 'wallet',
                    'amount' => $order->totalPrice,
                ]);
                $user->decrement('wallet', $order->totalPrice);
                DB::commit();
                return ApiResponse::sendResponse(true, 'Order Paid Successfully via Wallet');
            }

            if ($type === 'credit') {
                $transaction = Transaction::create([
                    'paymentId' => null,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'status' => 'pending',
                    // 'type' => 'credit',
                    'amount' => $order->totalPrice,
                ]);

                $data = [
                    'InvoiceValue' => $order->totalPrice,
                    'CustomerName' => "{$user->first_name} {$user->last_name}",
                    'NotificationOption' => 'ALL',
                    'CustomerEmail' => $user->email ?? 'test@gmail.com',
                    'DisplayCurrencyIso' => $country->currency,
                    'CustomerReference' => $order->id,
                    'MobileCountryCode' => $country->code,
                    'CustomerMobile' => $user->phone,
                    'CallBackUrl' => "https://mangamediaa.com/tawsel-hawe/public/orders-callback",
                    'ErrorUrl' => "https://mangamediaa.com/tawsel-hawe/public/orders-error",
                    'Language' => App::getLocale(),
                ];

                $mfObj = new MyFatoorahPayment($this->mfConfig);
                $paymentResponse = $mfObj->getInvoiceURL($data);

                if (!$paymentResponse || !isset($paymentResponse['invoiceURL'])) {
                    Log::error('Payment Failed: ' . json_encode($paymentResponse));
                    DB::rollBack();
                    return ApiResponse::errorResponse(false, 'Failed to initiate payment.');
                }

                $transaction->update(['paymentId' => $paymentResponse['invoiceId']]);

                DB::commit();

                return ApiResponse::sendResponse(true, 'Payment initiated successfully', [
                    'payment_url' => $paymentResponse['invoiceURL'],
                    'paymentId' => $paymentResponse['invoiceId'],
                ]);
            }
            return ApiResponse::errorResponse(false, 'Invalid payment method.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Error: ' . $e->getMessage());
            return ApiResponse::errorResponse(false, 'An error occurred while processing the payment.');
        }
    }
    public function rechargeWallet(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = Auth::user();
        $amount = $request->input('amount');

        if (!$user->first_name || !$user->last_name || !$user->phone) {
            return ApiResponse::errorResponse(false, 'User data is incomplete.');
        }

        if (!$user->country) {
            return ApiResponse::errorResponse(false, 'User country not found.');
        }

        $referenceId = 'wallet_recharge_' . uniqid();

        $data = [
            'InvoiceValue' => $amount,
            'CustomerName' => "{$user->first_name} {$user->last_name}",
            'NotificationOption' => 'ALL',
            'CustomerEmail' => $user->email ?? 'test@gmail.com',
            'DisplayCurrencyIso' => $user->country->currency,
            'CustomerReference' => $referenceId,
            'MobileCountryCode' => $user->country->code,
            'CustomerMobile' => $user->phone,
            'CallBackUrl' => "https://mangamediaa.com/tawsel-hawe/public/wallet/recharge-success",
            'ErrorUrl' => "https://mangamediaa.com/tawsel-hawe/public/orders-error",
            'Language' => App::getLocale(),
        ];

        try {
            DB::beginTransaction();

            $mfObj = new MyFatoorahPayment($this->mfConfig);
            $paymentResponse = $mfObj->getInvoiceURL($data);

            if (!$paymentResponse || !isset($paymentResponse['invoiceId'])) {
                Log::error('Payment Failed: ' . json_encode($paymentResponse));
                throw new \Exception('Failed to initiate wallet recharge.');
            }

            $paymentId = $paymentResponse['invoiceId'];

            // 🔹 إنشاء سجل في جدول Transactions
            $transaction = Transaction::create([
                'paymentId' => $paymentId,
                'amount' => $amount,
                'order_id' => null,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            DB::commit();

            return ApiResponse::sendResponse(true, 'Wallet recharge initiated successfully', [
                'payment_url' => $paymentResponse['invoiceURL'],
                'paymentId' => $paymentId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // 🔹 إلغاء العملية في حالة حدوث خطأ
            Log::error('Payment Error: ' . $e->getMessage());

            return ApiResponse::errorResponse(false, 'An error occurred while processing the wallet recharge.');
        }
    }




    public function walletRechargeSuccess(Request $request)
    {
        try {
            $paymentId = $request->input('paymentId');

            if (!$paymentId) {
                return view('payment.failed', ['message' => 'Invalid payment ID.']);
            }

            // 🔹 Check payment status using MyFatoorah
            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');

            if (!isset($data->InvoiceId, $data->CustomerReference, $data->InvoiceStatus)) {
                return view('payment.failed', ['message' => 'Failed to verify payment status.']);
            }

            // 🔹 Retrieve pending transaction for this invoice
            $transaction = Transaction::where('paymentId', $data->InvoiceId)
                ->where('status', 'pending')
                ->first();

            if (!$transaction) {
                return view('payment.failed', ['message' => 'No pending transaction found for this payment.']);
            }

            $user = User::find($transaction->user_id);
            if (!$user) {
                return view('payment.failed', ['message' => 'User not found.']);
            }

            DB::beginTransaction();

            // 🔹 Create a Wallet record
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'amount' => $transaction->amount,
                'type' => 'deposit',
                'status' => 'success',
            ]);

            // 🔹 Update the transaction status
            $transaction->update([
                'status' => 'success',
                'updated_at' => now(),
            ]);

            // 🔹 Increment user wallet balance
            $user->increment('wallet', $wallet->amount);

            DB::commit();

            return view('payment.success', compact('wallet'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet Recharge Error: ' . $e->getMessage());

            return view('payment.failed', ['message' => 'An error occurred while processing the wallet recharge.']);
        }
    }



    public function rechargeWalletError(Request $request)
    {
        try {
            $paymentId = $request->input('paymentId');

            if (!$paymentId) {
                return ApiResponse::errorResponse(false, 'Invalid payment ID.');
            }

            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');

            if (!isset($data->InvoiceId, $data->InvoiceStatus)) {
                return ApiResponse::errorResponse(false, 'Failed to retrieve payment status.');
            }

            $wallet = Wallet::where('paymentId', $paymentId)->where('status', 'pending')->first();

            if (!$wallet) {
                return ApiResponse::errorResponse(false, 'Wallet record not found.');
            }

            if (in_array($data->InvoiceStatus, ['Failed', 'Canceled'])) {
                $wallet->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);

                return ApiResponse::sendResponse(true, 'Payment failed, wallet status updated.', [
                    'status' => 'failed',
                    'balance' => Wallet::where('user_id', $wallet->user_id)->where('status', 'success')->sum('amount'),
                ]);
            }

            return ApiResponse::sendResponse(false, 'Payment is still pending.', [
                'status' => $data->InvoiceStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Wallet Recharge Error Callback: ' . $e->getMessage());
            return ApiResponse::errorResponse(false, 'An error occurred while processing the failed payment.');
        }
    }



    public function getWalletBalance()
    {
        $user = Auth::user();
         $fullName = "{$user->first_name} {$user->last_name}";
        $balance = number_format((float) $user->wallet, 2, '.', '');
        return ApiResponse::sendResponse(true, 'Wallet details retrieved successfully', [
            'balance' => $balance,
            'currency' => $user->country->currency,
             'name' => $fullName
        ]);
    }
    public function success(Request $request)
    {
        $paymentId = request('paymentId');
        $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
        $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');
        $orderId = $data->CustomerReference;
        $transaction = Transaction::where('paymentId', $data->InvoiceId)->where('order_id', $orderId)->first();
        $order = Order::where('id', $orderId)->first();
        if (!$transaction) {
            return ApiResponse::errorResponse(false, 'Transaction not found.');
        }
        // dd($order);
        $transaction->status = 'success';
        $transaction->save();
        // return ApiResponse::sendResponse(true, 'Payment successful', $order);
        return view('payment.success', compact('order'));
    }
    public function error(Request $request)
    {

        $paymentId = request('paymentId');
        $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
        $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');
        $orderId = $data->CustomerReference;
        $transaction = Transaction::where('paymentId', $data->InvoiceId)->first();
        // if (!$transaction) {
        //     return ApiResponse::errorResponse(false, 'Transaction not found.');
        // }
       
        $transaction->status = 'failed';
        $transaction->save();

        return view('payment.failed', compact('transaction'));
    }
    public function getAllWallets()
    {
        $user = Auth::user();
        $fullName = "{$user->first_name} {$user->last_name}";
        $wallets = Wallet::where('user_id', $user->id)
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($wallet) use ($fullName) {
                return [
                    'id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'amount' => $wallet->amount,
                    'status' => $wallet->status,
                    'type' => $wallet->type,
                    'created_at' => $wallet->created_at,
                    'full_name' => $fullName,
                ];
            });

        // $lastTransaction = Wallet::where('user_id', $user->id)
        //     ->select('created_at', 'type')
        //     ->latest()
        //     ->first();
        // $lastTransaction->created_at = $lastTransaction->created_at->format('Y-m-d');
        // $lastTransaction['amount'] = round($user->wallet, 2);
        // $lastTransaction['currency'] = $user->country->currency;
        $balance['amount'] = number_format((float) $user->wallet, 2, '.', '');
        $balance['currency'] = $user->country->currency;

        return ApiResponse::sendResponse(true, 'All wallets retrieved successfully', [
            'wallets' => $wallets,
            'balance' => $balance,
            'name' => $fullName
            
        ]);

    }
}