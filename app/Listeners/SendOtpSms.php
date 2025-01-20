<?php

namespace App\Listeners;

use App\Models\OtpCode;
use App\Notifications\SendOtpNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOtpSms
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
    public function handle(object $event): void
    {
        $otp = 1234;
        $user = $event->user;

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        Notification::send($user, new SendOtpNotification($otp));
    }
}
