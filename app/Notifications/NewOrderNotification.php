<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewOrderNotification extends Notification
{
    use Queueable;
    public $order;
    public function __construct(Order $order)
    {
        $this->order = $order;

    }
    /**
     * Create a new notification instance.
     */

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */

    public function toFcm($notifiable)
    {
        $locale = App::getLocale();
        $order = $this->order;
        $driver = $order->driver;
        return (new FcmMessage(notification: new FcmNotification(
            title: "{$driver->first_name} {$driver->last_name}",
            body: __("messages." . $order->status),

        )))
            // ->data([
            //     'order_id' => $this->order->id,
            //     'status' => $this->order->status,
            //     'is_read' => false,
            // ])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
                'apns' => [
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            // 'user_id' => $notifiable->id,
            // 'title' => $this->getTitle(App::getLocale()), // استخدام اللغة الحالية
            // 'description' => $this->getDescription(App::getLocale()), // استخدام اللغة الحالية
            // 'is_read' => 0,
        ];
    }
}