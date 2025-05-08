<?php

namespace App\Http\Requests;

use App\Trait\ApiResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
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
            // Driver ID validation
            'driver_id' => 'nullable|exists:drivers,id',

            // Order type validation
            'order_type' => 'required|in:inside,outside|string',
            'status_break' => 'required|in:notBreak,break',

            // Cover validation
            'cover' => 'required|in:cover,unCover|string',

            // Image validation
            // 'image' => 'required|image|mimes:jpeg,png,jpg',

            'product_name' => 'required|string|max:255',
            // Description validation
            'description' => 'required|string|max:500', // Limit description to 500 characters

            // Weight validation
            'weight' => 'required|numeric|min:0.1|max:1000',
            // Save Address validation
            'save_sender' => 'required|boolean',
            'save_receiver' => 'required|boolean',
            'area_sender_id' => 'required|exists:areas,id',
            'area_receiver_id' => 'required|exists:areas,id',
            // Receiver Address validation
            'name_receiver' => 'required|string|max:255',
            'phone_receiver' => 'required|string',
            'country_receiver' => 'required|string|max:255',
            'city_receiver' => 'required|string|max:255',
            'area_street_receiver' => 'required|string|max:255',
            'neighborhood_receiver' => 'required|string|max:255',
            'build_number_receiver' => 'required|string|max:50',
            'latitude_receiver' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < -90 || $value > 90) {
                        $fail('The ' . $attribute . ' must be a valid latitude.');
                    }
                },
            ],
            'longitude_receiver' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < -180 || $value > 180) {
                        $fail('The ' . $attribute . ' must be a valid longitude.');
                    }
                },
            ],

            // Sender Address validation
            'country_sender' => 'required|string|max:255',
            'city_sender' => 'required|string|max:255',
            'area_street_sender' => 'required|string|max:255',
            'neighborhood_sender' => 'required|string|max:255',
            'build_number_sender' => 'required|string|max:50',
            'latitude_sender' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < -90 || $value > 90) {
                        $fail('The ' . $attribute . ' must be a valid latitude.');
                    }
                },
            ],
            'longitude_sender' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < -180 || $value > 180) {
                        $fail('The ' . $attribute . ' must be a valid longitude.');
                    }
                },
            ],
        ];
    }

}