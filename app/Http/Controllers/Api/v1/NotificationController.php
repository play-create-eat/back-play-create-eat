<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\OneSignalService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function sendTestNotification(Request $request, OneSignalService $oneSignalService)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $success = $oneSignalService->sendNotification(
            [(string) $validated['user_id']],
            $validated['title'],
            $validated['message']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification sent successfully' : 'Failed to send notification',
        ]);

    }
}
