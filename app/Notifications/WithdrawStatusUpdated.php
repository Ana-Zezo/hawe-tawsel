<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class WithdrawStatusUpdated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $withdraw;

    public function __construct($withdraw)
    {
        $this->withdraw = $withdraw;
    }

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
        return (new FcmMessage(
            notification: new FcmNotification(
                title: __("messages.hawe-tawsel"),
                body: __("messages.withdraw_status", ['status' => $this->withdraw->status])
            )
        ))->custom([
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

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}