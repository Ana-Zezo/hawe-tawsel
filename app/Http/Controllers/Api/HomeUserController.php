<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Banner;
use App\Trait\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Banner\BannerResource;

class HomeUserController extends Controller
{
    public function index()
    {
        $user = Auth::guard('user')->user();

        // Get banners
        $banners = Banner::latest()->get();

        // جلب الطلبات الأخيرة
        // $orders = Order::where('user_id', $user->id)
        //     ->select(['id', 'orderNumber', 'city_receiver', 'city_sender', 'status', 'pickup_date', 'delivery_date'])
        //     ->latest()->limit(2)
        //     ->get();
    //     $orders = Order::where('user_id', $user->id)
    //     ->whereHas('transactions', function ($query) {
    //     $query->where('status', 'success'); // التحقق من حالة الدفع الناجحة
    //  })
    //     ->select(['id', 'orderNumber', 'city_receiver', 'city_sender', 'status', 'pickup_date', 'delivery_date'])
    //     ->latest()
    //     ->limit(2)
    //     ->get();
 $orders = Order::where('user_id', $user->id)
        ->whereHas('transactions', function ($query) use ($user) {
            $query->where('status', 'success')
                  ->where('user_id', $user->id); 
        })
        ->select(['id', 'orderNumber', 'city_receiver', 'city_sender', 'status', 'pickup_date', 'delivery_date'])
        ->latest()
        ->limit(2)
        ->get();
        $unreadCount = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('is_read', false)
            ->count();

        return ApiResponse::sendResponse(true, 'Data Retrieved Successfully', [
            'user' => [
                'name' => "{$user->first_name} {$user->last_name}",
                'image' => $user->image,
            ],
            'banners' => BannerResource::collection($banners),
            'order' => $orders->isNotEmpty() ? $orders : [],
            'notification' => $unreadCount
        ]);
    }

}