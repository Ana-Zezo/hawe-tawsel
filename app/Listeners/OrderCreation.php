<?php

namespace App\Listeners;

use App\Models\Driver;
use App\Models\Sender;
use App\Models\Reciever;
use App\Models\Transaction;
use App\Events\OrderCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderCreation
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $user = $order->user;
        if (!$user) {
            throw new \Exception('User not found for the order.');
        }
        $address = $user->addresses->first();
        if (!$address) {
            throw new \Exception('User address not found.');
        }
        $fullName = $user->first_name . " " . $user->last_name;
        Sender::create([
            'order_id' => $order->id,
            'sender_name' => $fullName,
            'sender_phone' => $user->phone,
            'sender_address' => $address->area_street,
            'longitude' => $address->longitude,
            'latitude' => $address->latitude,
        ]);
        Transaction::create([
            'paymentId' => null,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

    }
}