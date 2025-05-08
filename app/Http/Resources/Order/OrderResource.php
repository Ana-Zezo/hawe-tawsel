<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\DriverResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'rate' => $this->rate,
            'orderNumber' => $this->orderNumber,
            'user_id' => $this->user_id,
            'product_name' => $this->product_name,
            'break' =>$this->status_break,
            'driver_id' => $this->driver_id,

            'orderType' => $this->order_type,
            'cover' => $this->cover,
            'image' => $this->image,
            'description' => $this->description,
            'totalPrice' => $this->totalPrice,
            'coverPrice' => $this->coverPrice,
            'status' => $this->status,
            'weight' => $this->weight,
            'secret_key' => $this->secret_key,
            'dateReceipt' => $this->pickup_date ?? null,
            'dateDelivery' => $this->delivery_date
                ?? null,
            'area_sender_id' => $this->area_sender_id,
            'area_receiver_id'=>$this->area_receiver_id,
            'pickup_time' => $this->pickup_time ?? null,
            'delivery_time' => $this->delivery_time ?? null,
            'save_sender' => $this->save_sender,
            'save_receiver' => $this->save_receiver,
            'name_receiver' => $this->name_receiver,
            'phone_receiver' => $this->phone_receiver,
            'country_receiver' => $this->country_receiver,
            'city_receiver' => $this->city_receiver,
            'area_street_receiver' => $this->area_street_receiver,
            'neighborhood_receiver' => $this->neighborhood_receiver,
            'build_number_receiver' => $this->build_number_receiver,
            'latitude_receiver' => $this->latitude_receiver,
            'longitude_receiver	' => $this->longitude_receiver,
            'name_sender' => $this->name_sender,
            'phone_sender' => $this->phone_sender,
            'country_sender' => $this->country_sender,
            'city_sender' => $this->city_sender,
            'area_street_sender' => $this->area_street_sender,
            'neighborhood_sender' => $this->neighborhood_sender,
            'build_number_sender' => $this->build_number_sender,
            'latitude_sender' => $this->latitude_sender,
            'longitude_sender' => $this->longitude_sender,
            'basePrice' => $this->basePrice,
            'created_at' => dateTimeFormat($this->created_at),
            'updated_at' => dateTimeFormat($this->updated_at),

            'driver' => $this->when($this->driver_id, function () {
                return $this->driver ? new DriverResource($this->driver) : "null";
            }),

        ];
    }
}