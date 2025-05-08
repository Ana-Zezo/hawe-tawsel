<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Area;
use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Country;

use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // public function orders(Request $request)
    // {
    //     $countryId = $request->input('country_id');
    //     $dateInput = $request->input('data'); // expected format: 12-04-2025

    //     // تحويل التاريخ إلى كائن Carbon
    //     try {
    //         $date = Carbon::createFromFormat('d-m-Y', $dateInput)->startOfDay();
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid date format. Please use d-m-Y.',
    //         ], 422);
    //     }

    //     // استعلام المساحات التابعة للدولة
    //     $areaIds = Area::where('country_id', $countryId)->pluck('id');

    //     // فلترة حسب الشهر الحالي
    //     $startOfMonth = Carbon::now()->startOfMonth();
    //     $endOfMonth = Carbon::now()->endOfMonth();

    //     $usersCount = User::where('country_id', $countryId)
    //         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    //         ->count();

    //     $ordersCount = Order::whereIn('area_sender_id', $areaIds)
    //         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    //         ->count();

    //     $finishedCount = Order::whereIn('area_sender_id', $areaIds)
    //         ->where('status', 'finished')
    //         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    //         ->count();

    //     $finishedBackCount = Order::whereIn('area_sender_id', $areaIds)
    //         ->where('status', 'finishedBack')
    //         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    //         ->count();

    //     // الطلبات في نفس اليوم بالحالة المحددة
    //     $ordersInDay = Order::whereIn('area_sender_id', $areaIds)
    //         ->whereIn('status', ['finished', 'finishedBack'])
    //         ->whereDate('created_at', $date)
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Monthly report generated',
    //         'data' => [
    //             'users_count' => $usersCount,
    //             'orders_count' => $ordersCount,
    //             'finished_orders_count' => $finishedCount,
    //             'finished_back_orders_count' => $finishedBackCount,
    //             'orders_in_day' => $ordersInDay,
    //         ],
    //     ]);
    // }
    public function orders(Request $request)
    {
        // الحصول على country_id و التاريخ المدخل
        $countryId = $request->input('country_id');
        $dateInput = $request->input('date'); // Expected format: d-m-Y

        // التأكد من أن التاريخ المدخل بتنسيق صحيح
        try {
            // تحويل التاريخ المدخل إلى صيغة Carbon
            $date = Carbon::createFromFormat('d-m-Y', $dateInput);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date format. Please use d-m-Y.',
            ], 422);
        }

        // الحصول على جميع IDs للأماكن المتعلقة بالـ countryId
        $areaIds = Area::where('country_id', $countryId)->pluck('id');

        // جلب الطلبات التي حالتها "finished" و "finishedBack" فقط في التاريخ المحدد
        $ordersInDay = Order::whereIn('area_sender_id', $areaIds)
            ->whereIn('status', ['finished', 'finishedBack'])
            ->whereDate('created_at', $date)
            ->get();

        // حساب عدد الطلبات التي حالتها "finishedBack" فقط
        $finishedBackOrdersCount = Order::whereIn('area_sender_id', $areaIds)
            ->where('status', 'finishedBack')
            ->whereDate('created_at', $date)
            ->count();

        // إذا لم توجد بيانات في التاريخ المدخل، إعادة بيانات فارغة
        if ($ordersInDay->isEmpty()) {
            $ordersInDay = collect([]); // إعادة بيانات فارغة
        }

        // احسب عدد المستخدمين في التاريخ المدخل
        $usersCount = User::where('country_id', $countryId)
            ->whereDate('created_at', $date)
            ->count();

        // احسب إجمالي الطلبات في التاريخ المدخل
        $ordersCount = Order::whereIn('area_sender_id', $areaIds)
            ->whereDate('created_at', $date)
            ->count();

        // احسب عدد الطلبات المكتملة "finished"
        $finishedOrdersCount = Order::whereIn('area_sender_id', $areaIds)
            ->where('status', 'finished')
            ->whereDate('created_at', $date)
            ->count();

        // إعادة البيانات مع التفاصيل المطلوبة
        return response()->json([
            'status' => true,
            'message' => 'Monthly report generated',
            'data' => [
                'users_count' => $usersCount,
                'orders_count' => $ordersCount,
                'finished_orders_count' => $finishedOrdersCount,
                'finished_back_orders_count' => $finishedBackOrdersCount,
                'orders_in_day' => $ordersInDay,
            ]
        ]);
    }
    public function exportOrdersInDay(Request $request)
    {

        $countryId = $request->input('country_id');
        $dateInput = $request->input('date'); // Expected format: d-m-Y

        try {
            $date = Carbon::createFromFormat('d-m-Y', $dateInput)->startOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date format.',
            ], 422);
        }

        // الحصول على جميع IDs للأماكن المتعلقة بالـ countryId
        $areaIds = Area::where('country_id', $countryId)->pluck('id');

        // جلب الطلبات التي حالتها "finished" أو "finishedBack" فقط في التاريخ المحدد
        $ordersInDay = Order::whereIn('area_sender_id', $areaIds)
            ->whereIn('status', ['finished', 'finishedBack'])
            ->whereDate('created_at', $date)
            ->get();

        // تصدير البيانات إلى ملف Excel
        return Excel::download(new OrdersExport($ordersInDay), 'orders_in_day_' . $date->format('d-m-Y') . '.xlsx');
    }


    public function exportDrivers(Request $request)
    {
        $countryId = $request->input('country_id');

        $drivers = Driver::with(['country'])
            ->where('country_id', $countryId)
            ->get()
            ->map(function ($driver) {
                return [
                    'name' => $driver->first_name . " " . $driver->last_name,
                    'wallet' => $driver->wallet,
                    'phone' => ($driver->country->country_code ?? '') . $driver->phone,
                    'city' => $driver->city,
                    'totalOrder' => $driver->totalOrder,
                ];
            });
        $country = Country::where('id', $countryId)->pluck('name_en')->first();
        return Excel::download(new DriversExport($drivers), 'drivers_' . $country . '.xlsx');
    }

    public function drivers(Request $request)
    {
        $countryId = $request->input('country_id');


        $driverCount = Driver::with('country')->where('country_id', $countryId)->count();
        $drivers = Driver::with(['country'])
            ->where('country_id', $countryId)
            ->get()
            ->map(function ($driver) {
                return [
                    'image' => $driver->image,
                    'name' => $driver->first_name . " " . $driver->last_name,
                    'wallet' => $driver->wallet,
                    'phone' => ($driver->country->country_code ?? '') . $driver->phone,
                    'city' => $driver->city,
                    'totalOrder' => $driver->totalOrder
                ];
            });

        return ApiResponse::sendResponse(true, 'Data Retrieve successful', [
            'driverCount' => $driverCount,
            'drivers' => $drivers
        ]);

    }


}