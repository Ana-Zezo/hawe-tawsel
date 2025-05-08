<?php

namespace App\Http\Resources\Country;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
        'id' => $this->id,
        'country' => App::getLocale() === 'en' ? $this->name_en : $this->name_ar,
        'image' => $this->image,
        'country_code' => $this->country_code,
        'currency' => $this->currency,
        "kilo" => $this->kilo,
        "cover_price" => $this->cover_price,
        "tax_amount" => $this->tax_amount,
        'created_at' => dateTimeFormat($this->created_at),
        'updated_at' => dateTimeFormat($this->updated_at),
    ];

    // إذا كان هناك count مضاف للدولة (مثل withdraw_count أو complain_count)
    if (isset($this->withdraw_count)) {
        $data['withdraw_count'] = $this->withdraw_count;
    }

    if (isset($this->complain_count)) {
        $data['complain_count'] = $this->complain_count;
    }

    return $data;
    }
}