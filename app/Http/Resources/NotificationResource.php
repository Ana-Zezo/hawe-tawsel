<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');
        return [
            'id' => $this->id,
            'title' => $lang === 'ar' ? $this->title_ar : $this->title_en,
            'description' => $lang === 'ar' ? $this->description_ar : $this->description_en,
            'is_read' => $this->is_read
        ];
    }
}