<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string'],
        ]);

        $user = $request->user();

        $otp = OtpCode::where('code', $request->otp)
            ->where('user_id', $user->id)
            ->whereNull('expired_at')
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Invalid OTP code.'], 400);
        }

        $otp->update(['expired_at' => now()]);

        return response()->json(['message' => 'Account verified successfully.']);
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        $otp = OtpCode::where('user_id', $user->id)
            ->whereNull('expired_at')
            ->first();

        if ($otp) {
            return response()->json(['message' => 'OTP code already sent.'], 400);
        }

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => 123456,
        ]);

        return response()->json(['message' => 'OTP code sent successfully.']);
    }
}
