<?php

namespace App\Http\Resources\Area;

use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray($request): array
    {
        $lang = $request->header('lang', 'ar');

        return [
            'id' => $this->id,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'coordinates' => $this->coordinates,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius' => $this->radius,
            'created_at' => dateTimeFormat($this->created_at),
            'updated_at' => dateTimeFormat($this->updated_at),
        ];
    }

}