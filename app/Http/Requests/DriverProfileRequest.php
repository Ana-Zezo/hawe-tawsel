<?php

namespace App\Http\Requests;

use App\Trait\ApiResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Rules\ValidatePasswordUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class DriverProfileRequest extends FormRequest
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
                'message' => $validator->errors()->all(),
               
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
    $driver = Auth::guard('driver')->user();

    return [
        "first_name" => 'sometimes|string|min:3',
        "last_name" => "sometimes|string|min:3",
        "phone" => "sometimes|string|unique:drivers,phone,{$driver->id}",
        "image" => 'sometimes',
        "cart_image" => 'sometimes',
        "license_image" => 'sometimes',
        "license_self_image" => 'sometimes',
        "city" => 'sometimes|string|min:3',
        'latitude' => 'sometimes|numeric|between:-90,90',
        'longitude' => 'sometimes|numeric|between:-180,180',
        
        'current_password' => [
            'nullable',
            'string',
            'required_with:password',
            new ValidatePasswordUpdate('driver'),
        ],

        'password' => [
            'nullable',
            'string',
            'min:8',
            'confirmed',
        ],
    ];
}
}