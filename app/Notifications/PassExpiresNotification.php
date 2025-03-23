<?php

namespace App\Notifications;

use App\Models\Pass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class PassExpiresNotification extends Notification
{
    use Queueable;

    protected Pass $pass;
    protected int $minutes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pass $pass, int $minutes)
    {
        $this->pass = $pass;
        $this->minutes = $minutes;
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
            ->setSubject('Pass Expiration Warning')
            ->setBody("Your pass will expire in {$this->minutes} minutes.");
    }

    public function routeNotificationForOneSignal(): array
    {
        return ['include_external_user_ids' => $this->id];
    }
}
