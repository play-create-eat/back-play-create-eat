<?php

namespace App\Console\Commands;

use App\Models\Celebration;
use App\Services\OneSignalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-payment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders to users with upcoming celebrations';

    /**
     * Execute the console command.
     */
    public function handle(OneSignalService $oneSignalService): void
    {
        $twoHoursFromNow = Carbon::now()->addHours(2);
        $start = (clone $twoHoursFromNow)->subMinutes(5);
        $end = (clone $twoHoursFromNow)->addMinutes(5);

        $upcomingCelebrations = Celebration::whereBetween('celebration_date', [$start, $end])
            ->whereRaw('paid_amount < total_amount')
            ->with('user')
            ->get();

        $count = 0;

        foreach ($upcomingCelebrations as $celebration) {
            if ($celebration->user) {
                $userId = $celebration->user->id;
                $title = 'Payment Reminder';
                $message = "Your celebration starts in 2 hours! You still have AED" . number_format($amountDue, 2) . " outstanding balance. Please complete your payment.";
                $data = [
                    'celebration_id' => $celebration->id,
                    'amount_due' => $amountDue,
                ];

                $success = $oneSignalService->sendNotification(
                    [$userId],
                    $title,
                    $message,
                    $data
                );

                if ($success) {
                    $count++;
                }
            }
        }

        Log::info("Sent $count payment reminders");
    }
}
