<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class NewPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'exists:profiles,phone_number'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::whereHas('profile', function ($query) use ($request) {
            $query->where('phone_number', $request->input('phone_number'));
        })->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials provided.'
            ], 422);
        }

        $otp = OtpCode::where('identifier', $request->input('phone_number'))
            ->where('code', $request->input('otp'))
            ->where('purpose', PurposeEnum::FORGOT_PASSWORD)
            ->where('status', StatusEnum::VERIFIED)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Invalid or expired OTP code.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        $otp->update([
            'expires_at' => now()
        ]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
