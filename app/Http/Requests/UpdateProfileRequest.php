<?php

namespace App\Http\Requests;

use App\Trait\ApiResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Rules\ValidatePasswordUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
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
                'status' => false,
                'message' => $message,
                'errors' => $validator->errors()->all(),
            ]);


            throw new HttpResponseException($response);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $admin = Auth::guard('admin')->user();
        return [
            "name" => 'sometimes|string|min:3',
            "phone" => "sometimes|unique:admins,phone," . $admin->id,
            "country" => "sometimes|string|min:3",
            "city" => 'sometimes|string|min:3',
            "image" => 'sometimes',
            'current_password' => [
                'nullable',
                'string',
                new ValidatePasswordUpdate('admin'),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
                new ValidatePasswordUpdate('admin'),
            ],
        ];
        // Password::defaults() => if you want make password Strong 
    }
}