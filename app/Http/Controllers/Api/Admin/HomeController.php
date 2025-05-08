<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Banner;
use App\Models\Driver;
use App\Models\Country;
use App\Models\Complaint;
use App\Models\Withdraw;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{




    // public function index()
    // {
    //     try {
    //         // 🔹 جلب آخر Banner مع التحقق من وجود بيانات
    //         $lastBanner = Banner::latest()->first() ?? null;

    //         // 🔹 جلب الإحصائيات العامة
    //         $complaintTotal = Complaint::count();
    //         $totalOrders = Order::count();
    //         // $withdrawCount = Withdraw::count();
    //         $complaintTotal = Complaint::count();
    //         $totalUsers = User::count();
    //         $totalDrivers = Driver::count();

    //         // 🔹 جلب الدول المتاحة من قاعدة البيانات
    //         $countryIds = Country::pluck('id')->toArray();

    //         if (empty($countryIds)) {
    //             return ApiResponse::errorResponse(false, "No countries found in the database.");
    //         }

    //         // 🔹 جلب إحصائيات الدول
    //         $countryStats = Country::whereIn('id', $countryIds)
    //             ->select('id', 'name_en', 'name_ar') // 🔹 استخدام الحقول الصحيحة
    //             ->withCount(['drivers as drivers_count', 'users as users_count'])
    //             ->get()
    //             ->keyBy('id');

    //         // 🔹 جلب الطلبات حسب الدولة
    //         $ordersByCountry = User::whereIn('country_id', $countryIds)
    //             ->selectRaw('country_id, COUNT(*) as total_orders')
    //             ->groupBy('country_id')
    //             ->pluck('total_orders', 'country_id');

    //         // 🔹 تحديد اللغة المطلوبة
    //         $language = App::getLocale();// دالة تحدد اللغة ('en' أو 'ar')

    //         // 🔹 تجهيز البيانات للاستجابة
    //         $data = [
    //             'last_banner' => $lastBanner,
    //             'total_complaints' => $complaintTotal,
    //             'total_orders' => $totalOrders,
    //             'total_users' => $totalUsers,
    //             'total_drivers' => $totalDrivers,
    //             'country_stats' => [],
    //         ];

    //         foreach ($countryStats as $country) {
    //             $countryName = $language === 'ar' ? $country->name_ar : $country->name_en;
    //             $data['country_stats'][$countryName] = [
    //                 'drivers_count' => $country->drivers_count ?? 0,
    //                 'users_count' => $country->users_count ?? 0,
    //                 'orders_count' => $ordersByCountry,
    //             ];
    //         }

    //         return ApiResponse::sendResponse(true, "Dashboard statistics fetched successfully", $data);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         Log::error('Database Error: ' . $e->getMessage());
    //         return ApiResponse::errorResponse(false, "A database error occurred. Please try again later.");
    //     } catch (\Exception $e) {
    //         Log::error('General Error: ' . $e->getMessage());
    //         return ApiResponse::errorResponse(false, "An unexpected error occurred. Please try again.");
    //     }
    // }
    public function index()
    {
        try {
            $lastBanner = Banner::latest()->first() ?? null;

            $complaintTotal = Complaint::where('reply', null)->count();
            $withdrawCount = Withdraw::where('status', 'pending')->count();
            $totalOrders = Order::where('status', '!=', 'cancelled')->count();
            $totalUsers = User::where('is_verify', 1)->count();
            $totalDrivers = Driver::where('status', 'active')->where('is_approve', 1)->count();
            $totalDriversNotApprover = Driver::where('is_verify', 1)->where('status', 'active')->where('is_approve', 0)->count();
            $countryIds = Country::pluck('id')->toArray();

            if (empty($countryIds)) {
                return ApiResponse::errorResponse(false, "No countries found in the database.");
            }

            // 🔹 جلب إحصائيات الدول
            $countryStats = Country::whereIn('id', $countryIds)
                ->select('id', 'name_en', 'name_ar', 'image')
                ->withCount([
                    'drivers as drivers_count' => function ($query) {
                        $query->where('status', 'active')->where('is_approve', 1);
                    },
                    'users as users_count' => function ($query) {
                        $query->where('is_verify', 1);
                    },
                ])
                ->get()
                ->keyBy('id');

            // $ordersByCountry = User::whereIn('country_id', $countryIds)
            //     ->selectRaw('country_id, COUNT(*) as total_orders')
            //     ->groupBy('country_id')
            //     ->pluck('total_orders', 'country_id');
            $ordersByCountry = DB::table('orders')
                ->join('areas', 'orders.area_sender_id', '=', 'areas.id')
                ->select('areas.country_id', DB::raw('COUNT(orders.id) as total_orders'))
                ->groupBy('areas.country_id')
                ->pluck('total_orders', 'areas.country_id');


            // 🔹 تحديد اللغة المطلوبة
            $language = App::getLocale(); // 'ar' أو 'en'

            // 🔹 تجهيز بيانات الدول كقائمة (values)
            $countries = [];
            foreach ($countryStats as $country) {

                $countryName = $language === 'ar' ? $country->name_ar : $country->name_en;

                $countries[] = [
                    'country_name' => $countryName,
                    'country_image' => $country->image,
                    'drivers_count' => $country->drivers_count ?? 0,
                    'users_count' => $country->users_count ?? 0,
                    'orders_count' => $ordersByCountry[$country->id] ?? 0,
                ];
            }

            // 🔹 تجميع كل البيانات مع بعض
            $data = [
                'totalDriversNotApprover' => $totalDriversNotApprover,
                'last_banner' => $lastBanner,
                'total_complaints' => $complaintTotal,
                'withdraw_count' => $withdrawCount,
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'total_drivers' => $totalDrivers,
                'countries' => $countries,
            ];

            return ApiResponse::sendResponse(true, "Dashboard statistics fetched successfully", $data);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database Error: ' . $e->getMessage());
            return ApiResponse::errorResponse(false, "A database error occurred. Please try again later.");
        } catch (\Exception $e) {
            Log::error('General Error: ' . $e->getMessage());
            return ApiResponse::errorResponse(false, "An unexpected error occurred. Please try again.");
        }
    }


}