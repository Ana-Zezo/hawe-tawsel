<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;

class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'otp' => $this->otp,
            'country' => $this->country,
            'city' => $this->city,
            'phone' => $this->phone,
            'image' => $this->image,
            'token' => $this->token
        ];
    }
}