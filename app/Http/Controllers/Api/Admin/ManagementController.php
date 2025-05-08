<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Area;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Trait\ApiResponse;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ManagementController extends Controller
{
   public function index(Request $request)
{
    // التحقق من الـ validation
    $validated = $request->validate([
        'country_id' => 'required|exists:countries,id',
        'data' => [
            'required',
            'string',
            Rule::in(['user', 'driver', 'driverBlock', 'driverApprove', 'order', 'area']),
        ],
        'phone' => 'nullable|string',
        'orderNumber' => 'nullable|string',
    ]);


    $countryId = $validated['country_id'];
    $dataItem = $validated['data'];
     $phone = $request->input('phone');
    $orderNumber = $request->input('orderNumber');

    $response = [];
    $lang =App::getLocale();
    switch ($dataItem) {
        case 'user':
            $response['users'] = \App\Models\User::with('country')
                ->where('country_id', $countryId)
                ->when($phone, fn($q) => $q->where('phone', 'LIKE', "%$phone%"))
                ->get()
                ->map(function ($user) use ($lang) {
                    $user->country_name = $lang === 'ar' ? $user->country->name_ar : $user->country->name_en;
                    return $user;
                });
            break;


      case 'driver':
            $response['drivers'] = \App\Models\Driver::with('country')
                ->where('country_id', $countryId)
                ->where('status','active')
                ->where('is_approve',1)
                ->when($phone, fn($q) => $q->where('phone', 'LIKE', "%$phone%"))
                ->get()
                ->map(function ($driver) use ($lang) {
                    $driver->country_name = $lang === 'ar' ? $driver->country->name_ar : $driver->country->name_en;
                    return $driver;
                });
            break;

        case 'driverBlock':
           $response['driverBlock'] = \App\Models\Driver::with('country')
                ->where('country_id', $countryId)
                ->where('status', "block")
                ->when($phone, fn($q) => $q->where('phone', 'LIKE', "%$phone%"))
                ->get()
                ->map(function ($driver) use ($lang) {
                    $driver->country_name = $lang === 'ar' ? $driver->country->name_ar : $driver->country->name_en;
                    return $driver;
                });
            break;

        case 'driverApprove':
                $response['driverApprove'] = \App\Models\Driver::with('country')
                ->where('country_id', $countryId)
                ->where('is_approve', 0)
                 ->when($phone, fn($q) => $q->where('phone', 'LIKE', "%$phone%"))
                ->get()
                ->map(function ($driver) use ($lang) {
                    $driver->country_name = $lang === 'ar' ? $driver->country->name_ar : $driver->country->name_en;
                    return $driver;
                });
            break;


         case 'order':
                $response['orders'] = \App\Models\Order::with('areaSender.country')
                ->whereHas('areaSender', function ($query) use ($countryId) {
                    $query->where('country_id', $countryId);
                })
                 ->when($orderNumber, fn($q) => $q->where('orderNumber', 'LIKE', "%$orderNumber%"))
                ->get()
                ->map(function ($order) use ($lang) {
                    $order->country_name = optional($order->areaSender->country)->{'name_' . $lang};
                    return $order;
                });
            break;
        case 'area':
            $response['areas'] = \App\Models\Area::where('country_id', $countryId)->get();
            break;
    }

    return ApiResponse::sendResponse(true, 'Data Retrieve Successful', $response);
}

public function showUser(User $user)
    {
        return ApiResponse::sendResponse(true, 'Data Retrieve Successful', $user);
    }
    
     public function showDriver(Driver $driver)
    {
        return ApiResponse::sendResponse(true, 'Data Retrieve Successful', $driver);
    }

  public function showOrder(Order $order)
    {
        return ApiResponse::sendResponse(true, 'Data Retrieve Successful', $order);
    }


    public function updateDriver(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'is_approve' => 'nullable|boolean',
            'status' => 'nullable|in:active,block',
        ]);

        // تحديث القيم التي تم إرسالها فقط
        $driver->update($validated);

        return ApiResponse::sendResponse(true, 'Driver updated successfully', $driver);
    }
    public function deleteDriver(Driver $driver)
    {
        $driver->delete();
        $this->deletePreviousImages($driver);
        return ApiResponse::sendResponse(true, 'Driver Deleted Successful');
    }

    private function deletePreviousImages(Driver $user)
    {
        $imageFields = ['image', 'card_image', 'license_image', 'license_self_image'];

        foreach ($imageFields as $field) {
            if ($user->$field) {
                $path = str_replace(asset('storage/'), '', $user->$field);
                Storage::disk('public')->delete($path);
            }
        }
    }



}