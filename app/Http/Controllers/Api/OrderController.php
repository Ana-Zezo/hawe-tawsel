<?php

namespace App\Http\Controllers\Api;

use App\Models\Area;
use App\Models\User;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Wallet;
use App\Trait\ApiResponse;
use App\Models\Transaction;
use App\Events\OrderCreated;
use Illuminate\Http\Request;
use Vonage\Client\APIResource;
use App\Traits\UploadFileTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\Order\OrderResource;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Notification;
use App\Http\Resources\Review\ReviewResource;
use \App\Models\Notification as NotificationModel;
use App\Notifications\DriverCancelOrderNotification;
use App\Notifications\OrderStatusUpdatedNotification;

class OrderController extends Controller
{

    public function index(): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return ApiResponse::errorResponse(false, 'Unauthoriztion');
        }
        $orders = Order::useFilters()->dynamicPaginate();

        return ApiResponse::sendResponse(true, 'Data Retrieve Success', OrderResource::collection($orders));
    }
    // public function checkSingleCoordinate(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'statusCheck' => 'required|in:sender,reciever'
    //     ]);

    //     $lat = $request->latitude;
    //     $lng = $request->longitude;

    //     $areas = Area::all();
    //     $foundArea = null;

    //     foreach ($areas as $area) {
    //         if ($this->checkLocation($lat, $lng, $area)) {
    //             $foundArea = $area;
    //             break;
    //         }
    //     }

    //     if (!$foundArea) {
    //         return ApiResponse::errorResponse(false, 'Location is outside the allowed area');
    //     }

    //     return ApiResponse::sendResponse(true, 'Location is within the allowed area', ['area' => $foundArea]);
    // }
    // public function checkSingleCoordinate(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'status' => 'required|in:outside,inside',
    //         'statusCheck' => 'in:sender,receiver',
    //         'sender_area_id' => 'nullable|exists:areas,id', // اختياري لكن يجب أن يكون موجودًا في قاعدة البيانات
    //         'receiver_area_id' => 'nullable|exists:areas,id' // اختياري لكن يجب أن يكون موجودًا في قاعدة البيانات
    //     ]);

    //     $lat = $request->latitude;
    //     $lng = $request->longitude;
    //     $statusCheck = $request->statusCheck; // sender or receiver
    //     $status = $request->status; // inside or outside
    //     $senderAreaId = $request->sender_area_id;
    //     $receiverAreaId = $request->receiver_area_id;

    //     // جلب جميع المناطق من قاعدة البيانات
    //     $areas = Area::all();
    //     $foundArea = null;

    //     foreach ($areas as $area) {
    //         if ($this->checkLocation($lat, $lng, $area)) {
    //             $foundArea = $area;
    //             break;
    //         }
    //     }

    //     if (!$foundArea) {
    //         return ApiResponse::errorResponse(false, 'الموقع خارج المناطق المسموح بها');
    //     }

    //     // تحديث الـ sender أو receiver بناءً على statusCheck
    //     if ($statusCheck === 'sender') {
    //         $senderAreaId = $foundArea->id;
    //     } elseif ($statusCheck === 'receiver') {
    //         $receiverAreaId = $foundArea->id;
    //     }

    //     // التحقق من الشروط بناءً على status
    //     if ($status === 'inside') {
    //         if (!empty($senderAreaId) && !empty($receiverAreaId) && $senderAreaId !== $receiverAreaId) {
    //             return ApiResponse::errorResponse(false, 'يجب أن يكون المرسل والمستقبل في نفس المنطقة عند اختيار "داخل"');
    //         }
    //     } elseif ($status === 'outside') {
    //         if (!empty($senderAreaId) && !empty($receiverAreaId) && $senderAreaId === $receiverAreaId) {
    //             return ApiResponse::errorResponse(false, 'يجب أن يكون المستقبل في منطقة مختلفة عند اختيار "خارج"');
    //         }
    //     }

    //     return ApiResponse::sendResponse(true, 'الموقع صالح', [
    //         'area' => $foundArea,
    //         'sender_area_id' => $senderAreaId,
    //         'receiver_area_id' => $receiverAreaId
    //     ]);
    // }
public function checkSingleCoordinate(Request $request): JsonResponse
{
    $request->validate([
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'statusCheck' => 'required|in:sender,receiver',
        'status' => 'required|in:outside,inside',
        'sender_latitude' => 'nullable|numeric',
        'sender_longitude' => 'nullable|numeric'
    ]);

    $lat = $request->latitude;
    $lng = $request->longitude;
    $statusCheck = $request->statusCheck;
    $status = $request->status;

    // جلب جميع المناطق للتحقق من الإحداثيات
    $areas = Area::all();

    // تحديد المنطقة الحالية بناءً على الإحداثيات
    $foundArea = $this->findAreaByCoordinates($lat, $lng, $areas);

    if (!$foundArea) {
       return ApiResponse::errorResponse(false, __('messages.location_not_found'));
    }

    if ($statusCheck === 'sender') {
        return ApiResponse::sendResponse(true, __('messages.location_found'), [
            'area_sender_id' => $foundArea->id,
            'sender_latitude' => $lat,
            'sender_longitude' => $lng
        ]);
    }

    if ($statusCheck === 'receiver') {
        $request->validate([
            'sender_latitude' => 'required|numeric',
            'sender_longitude' => 'required|numeric'
        ]);

        // تحديد منطقة المرسل
        $senderArea = $this->findAreaByCoordinates($request->sender_latitude, $request->sender_longitude, $areas);

        if (!$senderArea) {
           return ApiResponse::errorResponse(false, __('messages.sender_location_invalid'));
        }

        // منطقة المستقبل
        $receiverArea = $foundArea;

        if ($status === 'inside' && $senderArea->id !== $receiverArea->id) {
              return ApiResponse::errorResponse(false, __('messages.inside_area_error'));
        }

        if ($status === 'outside' && $senderArea->id === $receiverArea->id) {
            return ApiResponse::errorResponse(false, __('messages.outside_area_error'));
        }

         return ApiResponse::sendResponse(true, __('messages.valid_location'), [
            'area_sender_id' => $senderArea->id,
            'area_receiver_id' => $receiverArea->id
        ]);
    }

    return ApiResponse::errorResponse(false, __('messages.invalid_request'));

}

/**
 * البحث عن المنطقة التي تحتوي على الإحداثيات
 */
private function findAreaByCoordinates($lat, $lng, $areas)
{
    foreach ($areas as $area) {
        if ($this->checkLocation($lat, $lng, $area)) {
            return $area;
        }
    }
    return null;
}



    private function isPointInPolygon($point, $polygon): bool
    {
        $x = $point['lat'];
        $y = $point['lng'];
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            if ($this->isPointOnEdge($x, $y, $xi, $yi, $xj, $yj)) {
                return true;
            }

            $intersect = (($yi > $y) != ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
    private function checkLocation($lat, $lng, $area): bool
    {
        $polygon = $area->coordinates; 
        if (!is_array($polygon)) {
            return false;
        }

        if ($this->isPointInPolygon(['lat' => $lat, 'lng' => $lng], $polygon)) {
            return true;
        }

        return $this->isWithinRadius($lat, $lng, $area->latitude, $area->longitude, $area->radius);
    }

    private function isPointOnEdge($x, $y, $x1, $y1, $x2, $y2): bool
    {
        if (($y < min($y1, $y2) || $y > max($y1, $y2)) || ($x < min($x1, $x2) || $x > max($x1, $x2))) {
            return false;
        }

        if ($x1 == $x2) {
            return abs($x - $x1) < 0.00001;
        }

        if ($y1 == $y2) {
            return abs($y - $y1) < 0.00001;
        }

        $m = ($y2 - $y1) / ($x2 - $x1);
        $b = $y1 - ($m * $x1);

        return abs($y - ($m * $x + $b)) < 0.00001;
    }

    private function isWithinRadius($lat1, $lng1, $lat2, $lng2, $radius): bool
    {
        $earthRadius = 6371000; // نصف قطر الأرض بالمتر

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $latDiff = $lat2 - $lat1;
        $lngDiff = $lng2 - $lng1;

        $a = sin($latDiff / 2) ** 2 + cos($lat1) * cos($lat2) * sin($lngDiff / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; 

        return $distance <= $radius;
    }


    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return round($distance / 1000, 2);
    }


    public function store(CreateOrderRequest $request): JsonResponse
    {
        $user = Auth::guard('user')->user();
        $country = $user->country;
        $orderData = $request->validated();
        $orderData['user_id'] = $user->id;
        $orderData['secret_key'] = rand(1111, 9999);
        $orderData['status'] = 'create';
        $orderData['name_sender'] = "{$user->first_name} {$user->last_name}";
        $orderData['phone_sender'] = $user->phone;
        $distance = $this->calculateDistance(
            $request->latitude_sender,
            $request->longitude_sender,
            $request->latitude_receiver,
            $request->longitude_receiver
        );

        $basePrice = $country->kilo * $distance;
        $coverPrice = ($request->cover === 'unCover') ? 0 : $user->country->cover_price;
        $totalPrice = round($basePrice + $coverPrice, 2);
        $orderData['coverPrice'] = $coverPrice;
        $orderData['totalPrice'] = $totalPrice;
        $orderData['basePrice'] = $basePrice;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $orderData['image'] = 'storage/' . $request->file('image')->store('uploads/images/orders', 'public');
        }

        $order = Order::create($orderData);

        return ApiResponse::sendResponse(true, 'Order created successfully', new OrderResource($order));
    }

    public function show(Order $order): JsonResponse
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            return ApiResponse::errorResponse(false, "Unauthorized");
        }

        // تحميل بيانات السائق والمراجعة
        $order->load('driver');

        return ApiResponse::sendResponse(
            true,
           __('messages.order_retrieved_successful'),
            new OrderResource($order),
        );
    }
   public function showDetails(Order $order): JsonResponse
    {
        
       $driver = Auth::user();
       $currency = $driver->country->currency;
        return ApiResponse::sendResponse(
            true,
            __('messages.order_retrieved_successful'),
            [
                'order' => new OrderResource($order), // ✅ تحويل الطلب إلى API Resource
                'currency' => $currency,
            ]
        );
    }




// public function getAreasForUserCountry(): JsonResponse
// {
//     $user = auth()->user(); // جلب بيانات المستخدم المصادق عليه
//     $country_id = $user->country_id; // الحصول على الدولة الخاصة بالمستخدم

//     if (!$country_id) {
//         return ApiResponse::errorResponse(false, __('messages.no_country_found'));
//     }

//     // جلب جميع المدن المرتبطة بدولة المستخدم مع المناطق الخاصة بها
//     $cities = Area::where('country_id', $country_id)->get();

//     if ($cities->isEmpty()) {
//         return ApiResponse::errorResponse(false, __('messages.no_regions_found'));
//     }

//     return ApiResponse::sendResponse(true, __('messages.areas_retrieved_successful'), $cities);
// }
public function getAreasForUserCountry(): JsonResponse
{
    $user = auth()->user();
    $country_id = $user->country_id;

    if (!$country_id) {
        return ApiResponse::errorResponse(false, __('messages.no_country_found'));
    }

    // تحديد اللغة المطلوبة من الهيدر أو القيمة الافتراضية
    $lang = request()->header('lang', app()->getLocale());

    // جلب جميع المناطق للدولة مع جميع بياناتها بدون `name_ar` و `name_en`
    $cities = Area::where('country_id', $country_id)->get()->map(function ($city) use ($lang) {
        return [
            'id'         => $city->id,
            'name'       => $lang === 'ar' ? $city->name_ar : $city->name_en,
            'country_id' => $city->country_id,
            'coordinates' => $city->coordinates,
            'status'     => $city->status,
            'latitude'   => $city->latitude,
            'longitude'  => $city->longitude,
            'radius'     => $city->radius,
            'created_at' => $city->created_at,
            'updated_at' => $city->updated_at,
        ];
    });

    if ($cities->isEmpty()) {
        return ApiResponse::errorResponse(false, __('messages.no_regions_found'));
    }

    return ApiResponse::sendResponse(true, __('messages.areas_retrieved_successful'), $cities);
}









    // Driver
    public function update(Request $request, Order $order): JsonResponse
    {
        $hasValidTransaction = $order->transactions()->where('status', 'success')->exists();
        if (!$hasValidTransaction) {
            return ApiResponse::errorResponse(false, 'This order does not have a successful transaction.');
        }

        $driver = Auth::guard('driver')->user();
        if (!$driver) {
            return ApiResponse::errorResponse(false, 'Unauthorized');
        }

        if ($driver->status == 'block' || $driver->is_verify == 0 || $driver->is_approve == 0) {
            return ApiResponse::errorResponse(false, 'You are not authorized to update this order');
        }

        if ($order->driver_id && $order->driver_id !== $driver->id) {
            return ApiResponse::errorResponse(false, 'You are not authorized to update this order');
        }

        $validatedData = $request->validate([
            'status' => 'required|in:bookOrder,receiveOrder,finished,back,finishedBack',
            'pickup_date' => 'required_if:status,bookOrder',
            'delivery_date' => 'required_if:status,bookOrder',
            'pickup_time' => 'required_if:status,bookOrder',
            'delivery_time' => 'required_if:status,bookOrder',
            'secret_key' => 'required_if:status,finished,finishedBack',
        ]);

        if (in_array($validatedData['status'], ['bookOrder']) && !$order->driver_id) {
            $order->update([
                'status' => $validatedData['status'],
                'driver_id' => $driver->id,
                'pickup_date' => $validatedData['pickup_date'],
                'delivery_date' => $validatedData['delivery_date'],
                'pickup_time' => $validatedData['pickup_time'],
                'delivery_time' => $validatedData['delivery_time'],
            ]);
        } elseif (in_array($validatedData['status'], ['finished', 'finishedBack'])) {
            if ($validatedData['secret_key'] !== $order->secret_key) {
                return ApiResponse::errorResponse(false, 'Invalid secret key');
            }
            $driver->increment('wallet', $order->totalPrice);
            $driver->increment('totalOrder');
            $order->update([
                'status' => $validatedData['status'],
            ]);
        } else {
            $order->update([
                'status' => $validatedData['status'],
            ]);
        }

        if ($order->user) {
            Notification::send($order->user, new NewOrderNotification($order));
            NotificationModel::create([
                'notifiable_id' => $order->user->id, 
                'notifiable_type' => get_class($order->user),
                'user_id' => $order->user->id,
                'title_ar' => "{$driver->first_name} {$driver->last_name}",
                'title_en' => "{$driver->first_name} {$driver->last_name}",
                'description_en' => __("messages." . $order->status, [], 'en'),
                'description_ar' => __("messages." . $order->status, [], 'ar'),
                'is_read' => false,
            ]);
            return ApiResponse::sendResponse(true, 'Order updated successfully', [
                'order' => new OrderResource($order),
            ]);
        }

        return ApiResponse::errorResponse(false, 'User not found for this order.');
    }

    public function getBookOrders(): JsonResponse
    {
        $driver = Auth::guard('driver')->user();
        if (!$driver) {
            return ApiResponse::errorResponse(false, 'Unauthorized');
        }
        $orders = Order::where('status', 'bookOrder')
            ->where('driver_id', $driver->id)
            ->get();
        return ApiResponse::sendResponse(true, 'Book orders retrieved successfully', OrderResource::collection($orders));
    }
    public function getReceiveOrder(): JsonResponse
    {
        $driver = Auth::guard('driver')->user();

        if (!$driver) {
            return ApiResponse::errorResponse(false, 'Unauthorized');
        }

        $orders = Order::where('status', 'receiveOrder')
            ->where('driver_id', $driver->id)
            ->get();

        return ApiResponse::sendResponse(true, 'ReceiveOrder orders retrieved successfully', OrderResource::collection($orders));
    }

    public function getFinishedOrders(): JsonResponse
    {
        $driver = Auth::guard('driver')->user();
        if (!$driver) {
            return ApiResponse::errorResponse(false, 'Unauthorized');
        }
        $orders = Order::where('status', 'finished')
            ->where('driver_id', $driver->id)
            ->get();

        return ApiResponse::sendResponse(true, 'Finished orders retrieved successfully', OrderResource::collection($orders));
    }


    public function cancelOrderByDriver(Order $order): JsonResponse
    {
        $driver = Auth::guard('driver')->user();

        if (!$driver) {
            return ApiResponse::errorResponse(false, 'Unauthorized');
        }

        // السماح بالإلغاء فقط إذا كان الطلب في حالة bookOrder
        if ($order->status !== 'bookOrder') {
            return ApiResponse::errorResponse(false, 'You cannot cancel this order');
        }

        if ($order->driver_id !== $driver->id) {
            return ApiResponse::errorResponse(false, 'You are not authorized to cancel this order');
        }

        $order->update([
            'driver_id' => null,
            'status' => 'create',
            'pickup_date' => null,
            'delivery_date' => null,
            'pickup_time' => null,
            'delivery_time' => null,
        ]);

        // إرسال إشعار للمستخدم
        Notification::send($order->user, new DriverCancelOrderNotification($order));

        // حفظ الإشعار في قاعدة البيانات
        NotificationModel::create([
             'notifiable_id' => $order->user->id, 
             'notifiable_type' => get_class($order->user),
            'title_ar' => __("messages.hawe-tawsel"),
            'title_en' => __("messages.hawe-tawsel"),
            'description_en' => __("messages.ordercancel", [], 'en'),
            'description_ar' => __("messages.ordercancel", [], 'ar'),
            'is_read' => false,
        ]);

        return ApiResponse::sendResponse(true, 'Order has been cancelled by the driver');
    }
  public function getOrdersByStatus(Request $request): JsonResponse
{
    $driver = Auth::guard('driver')->user();

    if (!$driver) {
        return ApiResponse::errorResponse(false, __('messages.unauthorized'));
    }

    $validStatuses = ['bookOrder', 'receiveOrder', 'finished', 'back', 'finishedBack'];

    // التحقق من أن الحالة المرسلة صحيحة
    $status = $request->input('status');

    if (!in_array($status, $validStatuses)) {
        return ApiResponse::errorResponse(false, __('messages.invalid_status'));
    }

    // جلب الطلبات حسب الحالة المختارة
    $orders = Order::where('status', $status)
        ->where('driver_id', $driver->id)
        ->get();



    return ApiResponse::sendResponse(true, __('messages.orders_retrieved_successfully'), [
            'order' => $orders,
            'currency' => $driver->country->currency,
        ]);
}


//  public function createOrder(Request $request)
// {
//     $driver = Auth::user();
//     $lang = App::getLocale();
//     $currency = $driver->country->currency;
//     $request->validate([
//         'order_type' => 'required|in:inside,outside',
//         'area_id' => 'nullable|exists:areas,id',
//     ]);

//     $orders = Order::query()
//         ->where('status', 'create')
//         ->whereHas('transactions', function ($query) {
//             $query->where('status', 'success');
//         });

//     if ($request->order_type == 'inside') {
//         $orders->whereColumn('area_sender_id', '=', 'area_receiver_id');
//     } elseif ($request->order_type == 'outside') {
//         $orders->whereColumn('area_sender_id', '<>', 'area_receiver_id');
//     }

//     if ($request->filled('area_id')) {
//         $orders->where(function ($query) use ($request) {
//             $query->where('area_sender_id', $request->area_id)
//                 ->orWhere('area_receiver_id', $request->area_id);
//         });
//     }

//     $orders = $orders->get();

//     if ($orders->isEmpty()) {
//         return ApiResponse::sendResponse(false, __('messages.no_orders_found'),[
//             'order' => []
//             ]);
//     }

//     return ApiResponse::sendResponse(true,  __('messages.orders_retrieved_successfully'), [
//         'orders' => OrderResource::collection($orders),
//         'currency' => $currency,
//     ]);
// }
public function createOrder(Request $request)
{
    $driver = Auth::user();
    $currency = $driver->country->currency;

    $request->validate([
        'order_type' => 'required|in:inside,outside',
        'area_id' => 'required|exists:areas,id',
    ]);
     $unreadCount = NotificationModel::where('notifiable_id', $driver->id)
            ->where('notifiable_type', get_class($driver))
            ->where('is_read', false)
            ->count();

    $orders = Order::where('status', 'create')
        ->whereNull('driver_id')
        ->whereHas('transactions', function ($query) {
            $query->where('status', 'success');
        });

    if ($request->order_type == 'inside') {
        // الطلبات داخل نفس المدينة
        $orders->whereColumn('area_sender_id', '=', 'area_receiver_id')
               ->where('area_sender_id', $request->area_id);
    } elseif ($request->order_type == 'outside') {
        // الطلبات بين مدن مختلفة
        $orders->whereRaw('area_sender_id <> area_receiver_id')
               ->where(function ($query) use ($request) {
                    $query->where('area_sender_id', $request->area_id);
               });
    }

    // **للتأكد من صحة الاستعلام، قم بفحص SQL قبل التنفيذ**
     // 🔎 للكشف عن أي مشكلة في الاستعلام

    $orders = $orders->get();

    if ($orders->isEmpty()) {
        return ApiResponse::sendResponse(false, __('messages.no_orders_found'), [
            'orders' => []
        ]);
    }

    return ApiResponse::sendResponse(true, __('messages.orders_retrieved_successfully'), [
        'orders' => OrderResource::collection($orders),
        'currency' => $currency,
        'notifications' =>$unreadCount
    ]);
}



    





    public function cancelOrder(Order $order)
    {
        $user = Auth::guard('user')->user();

        // Check if order is already booked by a driver
        if ($order->status === 'bookOrder') {
            return ApiResponse::errorResponse(false, "Cannot cancel order: Driver has already booked the order.");
        }

        // Check if order is already cancelled
        if ($order->status === 'cancelled') {
            return ApiResponse::errorResponse(false, "The order has already been cancelled.");
        }

        $transaction = Transaction::where('order_id', $order->id)->first();
        if (!$transaction || $transaction->status !== 'success') {

            return ApiResponse::errorResponse(false, "Cannot cancel order: Payment has not been completed.");
        }

        try {
            DB::beginTransaction();

            $wallet = Wallet::create([
                'user_id' => $user->id,
                'amount' => $order->totalPrice,
                'status' => 'success',
                'type' => 'cancelOrder'
            ]);

            $user->increment('wallet', $order->totalPrice);

            $order->update(['status' => 'cancelled']);

            DB::commit();

            return ApiResponse::sendResponse(true, 'Order cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorResponse(false, "Failed to cancel order. Please try again.");
        }
    }

    public function orderDelete(Order $order)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->where('id', $order->id)->first();
        if (!$order) {
            return ApiResponse::errorResponse(false, 'Not Found Order');
        }
        $image = str_replace('storage/', '', $order->image);
        if (Storage::disk('public')->exists($image)) {
            Storage::disk('public')->delete($image);
        }
        $order->delete();
    }
    public function orderReceiver()
    {
      $user = Auth::user();

      $orders = Order::where('phone_receiver', $user->phone)
       ->whereHas('transactions', function ($query) use ($user) {
        $query->where('status', 'success')
              ->where('user_id', $user->id); 
    })
    ->select(['id', 'orderNumber', 'status', 'city_receiver', 'city_sender', 'pickup_date', 'delivery_date'])
    ->latest()
    ->get();
    if ($orders->isEmpty()) {
        return ApiResponse::errorResponse(false, 'No Orders Exist for Receiver');
    }

    return ApiResponse::sendResponse(true, 'Orders Retrieved Successfully', $orders);
    }

    public function addressSender()
    {
        $user = Auth::user();

        // Get orders where save_sender is true
        $orders = Order::where('user_id', $user->id)
            ->where('save_sender', true)
            ->get();

        // If no saved sender addresses exist, return a response
        if ($orders->isEmpty()) {
            return ApiResponse::sendResponse(false, 'No Saved Sender Addresses Found', []);
        }

        // Map over each order and extract sender details
        $senderAddresses = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'name_sender' => $order->name_sender,
                'phone_sender' => $order->phone_sender,
                'country_sender' => $order->country_sender,
                'city_sender' => $order->city_sender,
                'area_street_sender' => $order->area_street_sender,
                'neighborhood_sender' => $order->neighborhood_sender,
                'build_number_sender' => $order->build_number_sender,
                'latitude_sender' => $order->latitude_sender,
                'longitude_sender' => $order->longitude_sender,
                'area_sender_id' => $order->area_sender_id,

            ];
        });

        return ApiResponse::sendResponse(true, 'Sender Addresses Retrieved Successfully', $senderAddresses);
    }
    public function addressReceiver(Request $request)
    {
        $user = Auth::user();
        if ($request->get('phone_receiver') !== null) {
            $phones = Order::where('user_id', $user->id)
                ->where('save_receiver', true)
                ->where('phone_receiver', $request->get('phone_receiver'))
                ->select([
                    'id',
                    'area_receiver_id',
                    'name_receiver',
                    'phone_receiver',
                    'country_receiver',
                    'city_receiver',
                    'area_street_receiver',
                    'neighborhood_receiver',
                    'build_number_receiver',
                    'latitude_receiver',
                    'longitude_receiver',
                ])
                ->get();
        } else {
            $phones = Order::where('user_id', $user->id)
                ->where('save_receiver', true)
                ->select([
                    'id',
                    'area_receiver_id',
                    'name_receiver',
                    'phone_receiver',
                    'country_receiver',
                    'city_receiver',
                    'area_street_receiver',
                    'neighborhood_receiver',
                    'build_number_receiver',
                    'latitude_receiver',
                    'longitude_receiver',
                ])
                ->get();
        }
        if ($phones->isEmpty()) {
            return ApiResponse::sendResponse(false, 'No Saved Receiver Addresses Found', []);
        }

        $senderAddresses = $phones->map(function ($order) {
            return [
                'id' => $order->id,
                'area_receiver_id' => $order->area_receiver_id,
                'name_receiver' => $order->name_receiver,
                'phone_receiver' => $order->phone_receiver,
                'country_receiver' => $order->country_receiver,
                'city_receiver' => $order->city_receiver,
                'area_street_receiver' => $order->area_street_receiver,
                'neighborhood_receiver' => $order->neighborhood_receiver,
                'build_number_receiver' => $order->build_number_receiver,
                'latitude_receiver' => $order->latitude_receiver,
                'longitude_receiver' => $order->longitude_receiver,
            ];
        });

        return ApiResponse::sendResponse(true, 'Sender Addresses Retrieved Successfully', $senderAddresses);
    }
    public function deleteOrderReceiver(Order $order)
    {
        $user = Auth::user();

        if ($order->save_receiver == 1)

            $order->update([
                $order->save_receiver = 0
            ]);
        return ApiResponse::sendResponse(true, "Data Deleted Successful", []);
    }
    public function deleteOrderSender(Order $order)
    {
        $user = Auth::user();

        if ($order->save_sender == 1)

            $order->update([
                $order->save_sender = 0
            ]);
        return ApiResponse::sendResponse(true, "Data Deleted Successful", []);
    }






    public function ordersUser()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
            ->select(['id', 'orderNumber', 'city_receiver', 'city_sender', 'status', 'pickup_date', 'delivery_date'])
            ->latest()->limit(2)
            ->get();

        if ($orders->isEmpty()) {
            return ApiResponse::sendResponse(true, 'No orders found for this user.', []);
        }

        return ApiResponse::sendResponse(true, 'Data Retrieve Successful', $orders);
    }









    public function orderSender()
    {
        $user = Auth::user();
  $orders = Order::where('user_id', $user->id)
            ->whereHas('transactions', function ($query) {
                $query->where('status', 'success'); // التحقق من حالة الدفع الناجحة
            })
            ->select(['id', 'orderNumber', 'city_receiver', 'city_sender', 'status', 'pickup_date', 'delivery_date'])
            ->latest()
            ->get();

        if ($orders->isEmpty()) {
            return ApiResponse::errorResponse(false, 'No Orders Found');
        }

        return ApiResponse::sendResponse(true, 'Data Retrieved Successfully', $orders);
    }
    
    
     public function rate(Request $request): JsonResponse
    {
        $user = Auth::guard('user')->user();
        $validatedData = $request->validate([
            'rate' => 'required',
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::where('id', $validatedData['order_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return ApiResponse::errorResponse(false, 'Order not found or unauthorized.');
        }

        $order->update(['rate' => $validatedData['rate']]);

        return ApiResponse::sendResponse(true, 'Order rate updated successfully', new OrderResource($order));
    }
    
     public function areaDriver()
    {
        $driver = Auth::user();
        $lang = App::getLocale();
        $areas = Area::where('country_id', $driver->country_id)
            ->select('id', $lang === 'ar' ? 'name_ar as name' : 'name_en as name')
            ->get();

        if ($areas->isEmpty()) {
            return ApiResponse::errorResponse(false, __('messages.no_regions_found'));
        }

        return ApiResponse::sendResponse(true, __('messages.areas_retrieved_successful'), $areas);
    }


public function orderStatus(Order $order)
    {
        $user = Auth::user();

        $transaction = Transaction::where('order_id', $order->id)->first();
      
        if (!$order) {
            return ApiResponse::errorResponse(false, __('messages.order_not_found'));
        }

        return ApiResponse::sendResponse(true, __('messages.order_retrieved'), [
            'orderNumber' => $order->orderNumber,
            'secretKey' => $order->secret_key,
            'status' => $transaction->status,
           
        ]);
    }
}
