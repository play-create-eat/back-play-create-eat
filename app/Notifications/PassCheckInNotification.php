<?php

namespace App\Notifications;

use App\Models\Pass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class PassCheckInNotification extends Notification
{
    use Queueable;

    protected Pass $pass;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pass $pass)
    {
        $this->pass = $pass;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [OneSignalChannel::class];
    }

    public function toOneSignal(): OneSignalMessage
    {
        $this->pass->loadMissing('children');

        return OneSignalMessage::create()
            ->setSubject('Pass Check-In')
            ->setBody('You have successfully checked in with your pass.');
    }

    public function routeNotificationForOneSignal(): array
    {
        return ['include_external_user_ids' => $this->id];
    }
}
