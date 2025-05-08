<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Admin;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Essa\APIToolKit\MediaHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\AdminResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\AdminRegisterRequest;
use App\Http\Requests\UpdateProfileRequest;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string|min:8',
            ]);
            $user = Admin::where('phone', $request->phone)->first();
            if (!Hash::check($request->password, $user->password)) {
                return ApiResponse::errorResponse(false, 'Invalid credentials.');
            }
            $user->tokens()->delete();
            $user['token'] = $user->createToken('user', ['app:all'])->plainTextToken;

            return ApiResponse::sendResponse(true, 'Login Successful!', new AdminResource($user));
        } catch (Exception $e) {
            return ApiResponse::sendResponse(true, 'Something went wrong.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'phone' => 'required|string'
        ]);
        $admin = Admin::where('phone', $request->phone)->first();
        if (!$admin) {
            return ApiResponse::errorResponse(false, 'admin not found');
        }
        if ($admin->otp != $request->otp) {
            return ApiResponse::errorResponse(false, 'Invalid OTP.');
        }
        return ApiResponse::sendResponse(true, 'Phone is successfully');
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
                'exists:admins,phone',
            ],
        ]);

        $otp = rand(111111, 999999);

        $user = Admin::where('phone', $request->phone)->first();

        if (!$user) {
            return ApiResponse::errorResponse(false, 'Admin not found');
        }
        $user->update(['otp' => $otp]);
        // OTPVerification::sendMsg($user->phone, 'Tawsel-Hawe', "Your OTP for password reset is: $otp");

        return ApiResponse::sendResponse(true, 'OTP sent successfully. Please verify to reset your password.');
    }
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'phone' => [
                    'required',
                    'string',
                    'exists:admins,phone',
                ],
                'password' => 'required|string|min:8|confirmed',

            ]);

            $user = Admin::where('phone', $request->phone)->first();

            if (!$user) {
                return ApiResponse::errorResponse(false, 'User not found');
            }

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return ApiResponse::sendResponse(true, 'Password reset successfully.');
        } catch (\Exception $e) {
            // Handle exceptions
            return ApiResponse::errorResponse(false, 'Something went wrong.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function profile(): JsonResponse
    {
        $user = Auth::guard('admin')->user();

        return ApiResponse::sendResponse(true, 'Data Retrieve Successfully', new AdminResource($user));
    }
    // public function updateProfile(UpdateProfileRequest $request): JsonResponse
    // {

    //     $user = Auth::user();
    //     $data = $request->validated();

    //     if (!isset($data['image']) || $data['image'] === null) {
    //         unset($data['image']);
    //     } elseif ($request->hasFile('image') && $request->file('image')->isValid()) {
    //         $file = $request->file('image');
    //         $path = 'uploads/images/users';

    //         // حذف الصورة القديمة إذا كانت موجودة
    //         if (!empty($user->image)) {
    //             $oldImagePath = str_replace('storage/', '', $user->image);
    //             if (Storage::disk('public')->exists($oldImagePath)) {
    //                 Storage::disk('public')->delete($oldImagePath);
    //             }
    //         }

    //         // حفظ الصورة الجديدة
    //         $uploadedFilePath = $file->store($path, 'public');
    //         $data['image'] = 'storage/' . $uploadedFilePath;
    //     }

    //     // منع تحديث كلمة المرور إذا لم يتم إرسالها
    //     if (!isset($data['password']) || empty($data['password'])) {
    //         unset($data['password']);
    //     } else {
    //         $data['password'] = Hash::make($data['password']);
    //     }

    //     $user->update($data);

    //     return ApiResponse::sendResponse(true, 'Data Updated Successfully', new AdminResource($user));
    // }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::guard('admin')->user();
        $data = $request->validated();

        if (!isset($data['image']) || $data['image'] === null) {
            unset($data['image']);
        } elseif ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $path = 'uploads/images/admins';

            // حذف الصورة القديمة إذا كانت موجودة
            if (!empty($user->image)) {
                $oldImagePath = str_replace('storage/', '', $user->image);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // حفظ الصورة الجديدة
            $uploadedFilePath = $file->store($path, 'public');
            $data['image'] = 'storage/' . $uploadedFilePath;
        }

        // منع تحديث كلمة المرور إذا لم يتم إرسالها
        if (!isset($data['password']) || empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return ApiResponse::sendResponse(true, 'تم تحديث البيانات بنجاح', new AdminResource($user));
    }

}