<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'country' => App::getLocale() === 'ar' ? $this->country->name_ar : $this->country->name_en,
            'image' => $this->image,
            'token' => $this->token
        ];
    }
}