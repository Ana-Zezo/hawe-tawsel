<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'otp' => $this->otp,
            'totalOrder' => $this->totalOrder,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'city' => $this->city,
            'neighborhood' => $this->neighborhood,
            'image' => $this->image,
            'card_image' => $this->card_image,
            'license_image' => $this->license_image,
            'license_self_image' => $this->license_self_image,
            'fcm_token' => $this->fcm_token,
            'country' => App::getLocale() === 'ar'
                ? optional($this->country)->name_ar
                : optional($this->country)->name_en,
            'token' => $this->token,
           
        ];
    }
}