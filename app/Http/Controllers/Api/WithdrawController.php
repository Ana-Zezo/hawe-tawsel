<?php

namespace App\Http\Controllers\Api;

use App\Models\Withdraw;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use App\Models\NotificationAdmin;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Notifications\WithdrawStatusUpdated;
use Google\Rpc\Context\AttributeContext\Request as AttributeContextRequest;

class WithdrawController extends Controller
{
   public function index(Request $request)
    {

        $driver = Auth::guard('driver')->user();
        $withdrawals = Withdraw::where('driver_id', $driver->id)->latest()->get();

        $walletBalance = $driver->wallet;

        return ApiResponse::sendResponse(true, 'Withdraw requests retrieved successfully', [
            'withdraws' => $withdrawals,
            'wallet' => $walletBalance,
            'currency' => $driver->country->currency
        ]);
    }
    // Driver
    public function store(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        if ($driver->wallet <= 0) {
            return ApiResponse::sendResponse(false, 'Your wallet balance is insufficient.');
        }

        $withdraw = Withdraw::create([
            'driver_id' => $driver->id,
            'amount' => $driver->wallet,
            'totalOrder' => $driver->totalOrder,
            'status' => 'pending',
            'country_id' => $driver->country_id,
        ]);
        $driver->wallet=0;
        $driver->totalOrder=0;
        $driver->decrement('wallet', $withdraw->amount);
        $driver->decrement('totalOrder',$withdraw->totalOrder);
        $driver->save();
       
        NotificationAdmin::create([
             'title' => "{$driver->first_name} {$driver->last_name}",
            'description' => "{$driver->phone}",
            'withdraw_id' => $withdraw->id,
            'is_read' => false
        ]);

        return ApiResponse::sendResponse(true, 'Withdrawal request submitted');
    }




    // Approve withdraw request Admin
    public function updateWithdrawStatus(Request $request, Withdraw $withdraw)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        if ($withdraw->status !== 'pending') {
            return ApiResponse::sendResponse(false, 'This withdrawal request has already been processed.');
        }

        $withdraw->update(['status' => $request->status]);

       


if ($request->status === 'rejected' && $withdraw->driver) {
    $driver = $withdraw->driver;
    $withdraw->driver->increment('wallet', $withdraw->amount);
    $withdraw->driver->increment('totalOrder', $withdraw->totalOrder);
}

        $withdraw->driver->notify(new WithdrawStatusUpdated($withdraw));
        ;

        Notification::create([
            'notifiable_id' => $withdraw->driver->id,
            'notifiable_type' => get_class($withdraw->driver),
            'title_en' => 'Hawe Tawsel',
            'title_ar' => 'هاوى توصيل',
             'description_en' => "Your withdrawal request of {$withdraw->amount} has been " . ($withdraw->status === 'approved' ? 'accepted' : 'rejected') . ".",
            'description_ar' => "طلب السحب الخاص بك بمبلغ {$withdraw->amount} تم " . ($withdraw->status === 'approved' ? 'قبوله' : 'رفضه') . ".",
            'is_read' => false
        ]);

        return ApiResponse::sendResponse(true, 'Withdrawal status updated');
    }
    
  public function AllWithdraws(Request $request)
{
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $countryId = $validated['country_id'];

    $withdraws =  Withdraw::with('driver')
        ->where('status', 'pending')
        ->where('country_id', $countryId)
        ->latest()
        ->get();

    return ApiResponse::sendResponse(true, 'Withdraw requests retrieved successfully', $withdraws);
}
// public function updateWithdrawStatus(Request $request, Withdraw $withdraw)
// {
//     $request->validate([
//         'status' => 'required|in:approved,rejected'
//     ]);

//     if ($withdraw->status !== 'pending') {
//         return ApiResponse::sendResponse(false, 'This withdrawal request has already been processed.');
//     }

//     $withdraw->update(['status' => $request->status]);

//     $driver = $withdraw->driver;

//     if ($request->status === 'approved' && $driver) {
//         $driver->update([
//             'totalOrder' => 0
//         ]);
//     }

//     if ($request->status === 'rejected' && $driver) {
//         $driver->increment('wallet', $withdraw->amount); // refund money only
//     }

//     if ($driver) {
//         $driver->notify(new WithdrawStatusUpdated($withdraw));

//         Notification::create([
//             'notifiable_id' => $driver->id,
//             'notifiable_type' => get_class($driver),
//             'title_en' => 'Hawe Tawsel',
//             'title_ar' => 'هاوى توصيل',
//             'description_en' => "Your withdrawal request of {$withdraw->amount} has been " . ($withdraw->status === 'approved' ? 'accepted' : 'rejected') . ".",
//             'description_ar' => "طلب السحب الخاص بك بمبلغ {$withdraw->amount} تم " . ($withdraw->status === 'approved' ? 'قبوله' : 'رفضه') . ".",
//             'is_read' => false
//         ]);
//     }

//     return ApiResponse::sendResponse(true, 'Withdrawal status updated');
// }

    
}