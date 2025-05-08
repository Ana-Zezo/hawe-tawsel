<?php

namespace App\Http\Requests;

use App\Trait\ApiResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\ValidatePasswordUpdate;

class UserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize(): bool
    {
        return true;
    }
    protected function failedValidation(Validator $validator)
    {
        if ($this->is('api/*')) {
            $lang = App::getLocale();
            $message = $lang === 'ar' ? 'خطأ في التحقق' : 'Validation Error';

            $response = response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $validator->errors()->all(),
            ], 422);

            throw new HttpResponseException($response);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    // public function rules(): array
    // {
    //     $user = Auth::guard('user')->user();

    //     return [
    //         "first_name" => 'sometimes|string|min:3',
    //         "last_name" => "sometimes|string|min:3",
    //         "phone" => "sometimes|unique:admins,phone," . $user->id,
    //         "image" => 'sometimes|image|mimes:jpeg,png,jpg',
    //         "city" => 'sometimes|string|min:3',

    //         // `current_password` مطلوب فقط إذا أدخل المستخدم كلمة مرور جديدة
    //         'current_password' => [
    //             'nullable',
    //             'string',
    //             function ($attribute, $value, $fail) {
    //                 if (!empty(request()->password) && empty($value)) {
    //                     $fail(__('validation.required', ['attribute' => 'current password']));
    //                 }
    //             },
    //             'current_password:user'
    //         ],

    //         // `password` مطلوب فقط إذا أدخل المستخدم `current_password`
    //         'password' => [
    //             'nullable',
    //             'string',
    //             'min:8',
    //             'confirmed',
    //             function ($attribute, $value, $fail) {
    //                 if (!empty($value) && empty(request()->current_password)) {
    //                     $fail(__('validation.required', ['attribute' => 'current password']));
    //                 }
    //             }
    //         ],
    //     ];
    // }
    public function rules(): array
    {
        $user = Auth::guard('user')->user();
        $currentPasswordProvided = !empty(request()->current_password);


        return [
            "first_name" => 'sometimes|string|min:3',
            "last_name" => "sometimes|string|min:3",
            "phone" => "sometimes|unique:users,phone," . $user->id,
            "image" => 'sometimes',
            "city" => 'nullable|string|min:3',
            // 'current_password' => [
            //     'nullable',
            //     'string',
            //     new ValidatePasswordUpdate('user'),
            // ],

            // 'password' => [
            //     'nullable',
            //     'string',
            //     'min:8',
            //     'confirmed',
            //     new ValidatePasswordUpdate('user'),
            // ],
            'current_password' => [
                'nullable',
                'string',
                new ValidatePasswordUpdate('user'),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
                new ValidatePasswordUpdate('user'),
            ],

        ];
    }




}