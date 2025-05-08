<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Country;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use App\Trait\OTPVerification;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserProfileRequest;
use App\Http\Requests\UserRegisterRequest;

class AuthController extends Controller
{
    // public function register(RegisterRequest $request)
    // {
    //     $data = $request->validated();
    //     $data['otp'] = rand(111111, 999999);
    //     $country = Country::find($data['country_id']);
    //     if (!$country) {
    //         return ApiResponse::errorResponse(false, __('Invalid country selected'));
    //     }
    //     // Make Concatenation Code + Phone In SMS Message
    //     // $data['phone'] = $country->country_code . $data['phone'];
    //     $data['password'] = isset($data['password']) ? Hash::make($data['password']) : null;

    //     $user = User::updateOrCreate(
    //         ['phone' => $data['phone']],
    //         array_filter($data)
    //     );
    //     $user['token'] = $user->createToken('user')->plainTextToken;
    //     $user->load('country');
    //     return ApiResponse::sendResponse(true, __('User Account Created Successfully'), new UserResource($user));
    // }
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['otp'] = rand(111111, 999999);

        $country = Country::find($data['country_id']);
        if (!$country) {
            return ApiResponse::errorResponse(false, __('Invalid country selected'));
        }

        $data['password'] = isset($data['password']) ? Hash::make($data['password']) : null;

        $user = User::where('phone', $data['phone'])->first();

        if ($user) {
            if ($user->is_verify == 1) {
                return ApiResponse::errorResponse(false, __('This account is already verified and cannot register again.'), null, 403);
            }
            $this->deletePreviousImages($user);
        }
        $user = User::updateOrCreate(
            ['phone' => $data['phone']],
            array_filter($data)
        );
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = $this->storeImage($request->file('image'), 'uploads/images/users');
        }
        $user->update($data);
        $user['token'] = $user->createToken('user')->plainTextToken;
        $user->load('country');

        return ApiResponse::sendResponse(true, __('User Account Created Successfully'), new UserResource($user));
    }

    /**
     * حذف الصور السابقة إذا وجدت
     */
    private function deletePreviousImages(User $user)
    {
        if ($user->image) {
            $path = str_replace(asset('storage/'), '', $user->image);
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * حفظ الصورة وإرجاع المسار
     */
    private function storeImage($file, $directory)
    {
        $path = $file->store($directory, 'public');
        return asset('storage/' . $path);
    }


    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string|exists:users,phone',
                'password' => 'required|string|min:8',
            ]);
            $user = User::where('phone', $request->phone)->first();
            if (!$user) {
                return ApiResponse::sendResponse(false, _('Phone number does not exist. Please register'));
            }

            if (!$user->is_verify) {
                return ApiResponse::sendResponse(false, _('Phone number not verified. Please verify to log in'));
            }

            if (!Hash::check($request->password, $user->password)) {
                return ApiResponse::errorResponse(false, __('messages.credentials'));
            }

            // if ($request->has('fcm_token') && $user->fcm_token !== $request->fcm_token) {
            //     $user->update(['fcm_token' => $request->fcm_token]);
            // }
            if ($request->filled('fcm_token') && $user->fcm_token !== $request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }

            $user->tokens()->delete();
            $user["token"] = $user->createToken('Bearer ', ['app:all'])->plainTextToken;

            return ApiResponse::sendResponse(true, 'Login Successful!', new UserResource($user));
        } catch (Exception $e) {
            return ApiResponse::sendResponse(false, __("messages.something_went_wrong"), [
                'error' => $e->getMessage(),
            ]);
        }
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'phone' => 'required|exists:users,phone'
        ]);
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return ApiResponse::sendResponse(false, 'User not found');
        }

        if ($user->otp != $request->otp) {
            return ApiResponse::sendResponse(false, 'Invalid OTP.');
        }
        $route = route('verify-otp');

        // dd('asd');
        $user->update([
            'is_verify' => true,
        ]);

        return ApiResponse::sendResponse(true, 'Phone verified successfully');
    }

    // public function logout()
    // {
    //     try {
    //         // $user = $request->user();
    //         $user = Auth::guard('user')->user();
    //         if (!$user) {
    //             return ApiResponse::sendResponse(false, 'No authenticated user.');
    //         }
    //         $user->currentAccessToken()->delete();
    //         return ApiResponse::sendResponse(true, 'Logout Successful!');
    //     } catch (\Exception $e) {
    //         return ApiResponse::sendResponse(false, 'Something went wrong.', [
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }
    public function logout()
{
    try {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return ApiResponse::sendResponse(false, 'No authenticated user.');
        }

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
            return ApiResponse::sendResponse(true, 'Logout Successful!');
        }

        return ApiResponse::sendResponse(false, 'No active session found.');
    } catch (\Exception $e) {
        return ApiResponse::sendResponse(false, 'Something went wrong.', [
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
                'exists:users,phone',
            ],
        ]);

        $otp = rand(111111, 999999);
        $user = User::where('phone', $request->phone)->where('is_verify', true)->first();

        if (!$user) {
            return ApiResponse::sendResponse(false, 'User not found');
        }
        $user->update(['otp' => $otp, 'is_verify' => true]);
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
                    'exists:users,phone',
                ],
                'password' => 'required|string|min:8|confirmed',

            ]);

            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return ApiResponse::sendResponse(false, 'User not found');
            }


            $user->update([
                'password' => Hash::make($request->password),
            ]);


            return ApiResponse::sendResponse(true, 'Password reset successfully.');
        } catch (\Exception $e) {
            return ApiResponse::sendResponse(false, __("messages.something_went_wrong"), [
                'error' => $e->getMessage(),
            ]);

        }
    }

    public function profile()
    {
        $user = Auth::guard('user')->user();
        return ApiResponse::sendResponse(true, 'Data Retrieve Successfully', new UserResource($user));
    }
    // public function updateProfile(UserProfileRequest $request)
    // {
    //     $user = Auth::guard('user')->user();
    //     $data = $request->validated();

    //     if ($request->hasFile('image') && $request->file('image')->isValid()) {
    //         $file = $request->file('image');
    //         $path = 'uploads/images/users';

    //         if (!empty($user->image)) {
    //             $oldImagePath = str_replace('storage/', '', $user->image);
    //             if (Storage::disk('public')->exists($oldImagePath)) {
    //                 Storage::disk('public')->delete($oldImagePath);
    //             }
    //         }


    //         $uploadedFilePath = $file->store($path, 'public');
    //         $data['image'] = 'storage/' . $uploadedFilePath;
    //     }

    //     $user->update($data);

    //     return ApiResponse::sendResponse(true, 'Data Updated Successfully', new UserResource($user));
    // }


    public function resetCode(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string'
        ]);
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return ApiResponse::sendResponse(false, 'User not found');
        }
        $data['otp'] = rand(111111, 999999);
        $user->update($data);
        return ApiResponse::sendResponse(true, 'Code Resend Successful');
    }
    // public function updateProfile(UserProfileRequest $request)
    // {
    //     $user = Auth::guard('user')->user();
    //     $data = $request->validated();

    //     // Handle image upload
    //     if ($request->hasFile('image') && $request->file('image')->isValid()) {
    //         $file = $request->file('image');
    //         $path = 'uploads/images/users';

    //         if (!empty($user->image)) {
    //             $oldImagePath = str_replace('storage/', '', $user->image);
    //             if (Storage::disk('public')->exists($oldImagePath)) {
    //                 Storage::disk('public')->delete($oldImagePath);
    //             }
    //         }

    //         $uploadedFilePath = $file->store($path, 'public');
    //         $data['image'] = 'storage/' . $uploadedFilePath;
    //     }

    //     // Prevent password from being updated if not provided
    //     if (empty($data['password'])) {
    //         unset($data['password']);
    //     } else {
    //         $data['password'] = Hash::make($data['password']);
    //     }

    //     $user->update($data);

    //     return ApiResponse::sendResponse(true, 'Data Updated Successfully', new UserResource($user));
    // }
    public function updateProfile(UserProfileRequest $request)
    {
        $user = Auth::guard('user')->user();
        $data = $request->validated();

        // منع تحديث الصورة إذا لم يتم إرسالها أو كانت null
        if (!isset($data['image']) || $data['image'] === null) {
            unset($data['image']);
        } elseif ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $path = 'uploads/images/users';

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

        return ApiResponse::sendResponse(true, 'Data Updated Successfully', new UserResource($user));
    }



}