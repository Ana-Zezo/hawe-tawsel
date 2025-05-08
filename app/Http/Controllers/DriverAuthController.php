<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverProfileRequest;
use Exception;
use App\Models\Driver;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Essa\APIToolKit\MediaHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\DriverRegisterRequest;
use App\Http\Resources\UserResource;

class DriverAuthController extends Controller
{

 
    public function register(DriverRegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($request->password);
        $data['otp'] = rand(111111, 999999);

        $user = Driver::where('phone', $data['phone'])->first();

        if ($user) {
            // التحقق من حالة is_verify
            if ($user->is_verify == 1) {
                return ApiResponse::sendResponse(false, 'This account is already verified and cannot register again.', null);
            }

            // حذف الصور السابقة إن وجدت
            $this->deletePreviousImages($user);
        }

        // إنشاء أو تحديث الحساب
        $user = Driver::updateOrCreate(
            ['phone' => $data['phone']],
            array_filter($data)
        );

        // حفظ الصور الجديدة
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = $this->storeImage($request->file('image'), 'storage/uploads/images/drivers/image');
        }

        if ($request->hasFile('card_image') && $request->file('card_image')->isValid()) {
            $data['card_image'] = $this->storeImage($request->file('card_image'), 'storage/uploads/images/drivers/cardImage');
        }

        if ($request->hasFile('license_image') && $request->file('license_image')->isValid()) {
            $data['license_image'] = $this->storeImage($request->file('license_image'), 'storage/uploads/images/drivers/licenseImage');
        }

        if ($request->hasFile('license_self_image') && $request->file('license_self_image')->isValid()) {
            $data['license_self_image'] = $this->storeImage($request->file('license_self_image'), 'storage/uploads/images/drivers/licenseSelfImage');
        }

        $user->update($data);

        $user['token'] = $user->createToken('user')->plainTextToken;

        if ($request->filled('fcm_token') && (!$user->fcm_token || $user->fcm_token !== $request->fcm_token)) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return ApiResponse::sendResponse(true, 'User Account Created Successfully', new DriverResource($user));
    }

    /**
     * حذف الصور السابقة إذا وجدت
     */
    private function deletePreviousImages(Driver $user)
    {
        $imageFields = ['image', 'card_image', 'license_image', 'license_self_image'];

        foreach ($imageFields as $field) {
            if ($user->$field) {
                $path =  $user->$field;
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * حفظ الصورة وإرجاع المسار
     */
  private function storeImage($file, $directory)
    {
    return $file->store($directory, 'public'); 
    }


    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string|min:8',
            ]);
            $user = Driver::where('phone', $request->phone)->first();
            if (!$user) {
                return ApiResponse::errorResponse(false, 'Phone number does not exist. Please register.');
            }
            if (!$user->is_verify) {
                return ApiResponse::errorResponse(false, 'Phone number not verified. Please verify to log in.');
            }
            if (!$user->is_approve) {
                return ApiResponse::errorResponse(false, 'Account Not Approve. Wait To Approve Account');
            }
            if (!Hash::check($request->password, $user->password)) {
                return ApiResponse::errorResponse(false, __('messages.credentials'));
            }
            if ($request->filled('fcm_token') && $user->fcm_token !== $request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }

             if ($request->filled('fcm_token') && $user->fcm_token !== $request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }
            if ($request->filled('fcm_token')) {
                 $user->update(['fcm_token' => $request->fcm_token]);
              }
             if ($request->filled('fcm_token') && (!$user->fcm_token || $user->fcm_token !== $request->fcm_token)) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

            $user->tokens()->delete();
            $user['token'] = $user->createToken('user', ['app:all'])->plainTextToken;

            return ApiResponse::sendResponse(true, 'Login Successful!', new DriverResource($user));
        } catch (Exception $e) {
            return ApiResponse::errorResponse(false, $e->getMessage());
        }
    }

    public function verifyOtp(Request $request)
    {

        $request->validate([
            'otp' => 'required|string',
            'phone' => 'required|string'
        ]);
        $user = Driver::where('phone', $request->phone)->first();
        if (!$user) {
            return ApiResponse::errorResponse(false, 'User not found');
        }
        if ($user->otp != $request->otp) {
            return ApiResponse::errorResponse(false, 'Invalid OTP.');
        }
        $user->update([
            'is_verify' => true,
        ]);

        return ApiResponse::sendResponse(true, 'Phone verified successfully');
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return ApiResponse::errorResponse(false, 'Unauthorized: No authenticated user.');
            }
            $user->currentAccessToken()->delete();
            return ApiResponse::sendResponse(true, 'Logout Successful!');
        } catch (\Exception $e) {
            return ApiResponse::errorResponse(false, 'Something went wrong.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'string',
                'exists:drivers,phone',
            ],
        ]);

        $otp = rand(111111, 999999);


        $driver = Driver::where('phone', $request->phone)->first();

        if (!$driver) {
            return ApiResponse::sendResponse(404, 'Driver not found');
        }
        $driver->update(['otp' => $otp]);
        // OTPVerification::sendMsg($driver->phone, 'Tawsel-Hawe', "Your OTP for password reset is: $otp");

        return ApiResponse::sendResponse(true, 'OTP sent successfully. Please verify to reset your password.');
    }
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'phone' => [
                    'required',
                    'string',
                    'exists:drivers,phone',
                ],
                'password' => 'required|string|min:8|confirmed',

            ]);

            $user = Driver::where('phone', $request->phone)->first();

            if (!$user) {
                return ApiResponse::errorResponse(false, 'User not found');
            }

            $user->update([
                'password' => Hash::make($request->password),
                'is_verify' => true
            ]);

            return ApiResponse::sendResponse(true, 'Password reset successfully.');
        } catch (\Exception $e) {
            return ApiResponse::errorResponse(false, 'Something went wrong.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function profile()
    {
        $user = Auth::guard('driver')->user();
        // $user['image'] = $user->image ? asset($user->image) : null;
        // $user['card_image'] = $user->card_image ? asset($user->card_image) : null;
        // $user['license_image'] = $user->license_image ? asset($user->license_image) : null;
        // $user['license_self_image'] = $user->license_self_image ? asset($user->license_self_image) : null;

        return ApiResponse::sendResponse(true, 'Data Retrieve Successfully', new DriverResource($user));
    }
public function updateProfile(DriverProfileRequest $request)
{
    $driver = Auth::guard('driver')->user();
    $data = $request->validated();

    // Image fields
    $imageFields = ['image', 'cart_image', 'license_image', 'license_self_image'];
    $uploadPath = 'uploads/images/drivers';

    foreach ($imageFields as $field) {
        if (!isset($data[$field]) || $data[$field] === null) {
            unset($data[$field]);
        } elseif ($request->hasFile($field) && $request->file($field)->isValid()) {
            $file = $request->file($field);

            // Delete old image if exists
            if (!empty($driver->$field)) {
                $oldImagePath = str_replace('storage/', '', $driver->$field);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // Save new image
            $uploadedFilePath = $file->store($uploadPath, 'public');
            $data[$field] = 'storage/' . $uploadedFilePath;
        }
    }

    // Prevent password update if not provided
    if (!isset($data['password']) || empty($data['password'])) {
        unset($data['password']);
    } else {
        $data['password'] = Hash::make($data['password']);
    }

    $driver->update($data);

    return ApiResponse::sendResponse(true, 'Profile Updated Successfully', new DriverResource($driver));
}

}