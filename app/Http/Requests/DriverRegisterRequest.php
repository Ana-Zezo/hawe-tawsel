<?php

namespace App\Http\Requests;

use App\Trait\ApiResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class DriverRegisterRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => [
                'required',
                'string',
                // 'unique:drivers,phone',
            ],
            'longitude' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < -180 || $value > 180) {
                        $fail('The ' . $attribute . ' must be a valid longitude.');
                    }
                },
            ],
            'fcm_token' => 'required|string',
            'latitude' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < -90 || $value > 90) {
                        $fail('The ' . $attribute . ' must be a valid latitude.');
                    }
                },
            ],
            'country_id' => 'required|exists:countries,id',
            'city' => 'required|string|max:255',
            'neighborhood' => 'required|string|max:255',
            'image' => "required|image|mimes:jpeg,png,jpg",
            'card_image' => "required|image|mimes:jpeg,png,jpg",
            'license_image' => "required|image|mimes:jpeg,png,jpg",
            'license_self_image' => "required|image|mimes:jpeg,png,jpg",
        ];
    }
}