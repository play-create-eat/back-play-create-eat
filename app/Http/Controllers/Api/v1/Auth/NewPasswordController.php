<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use App\Enums\Otps\TypeEnum;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class NewPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => ['required_without:otp', 'string', 'exists:profiles,phone_number'],
            'otp' => ['required_without:phone_number', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Log::info('New password request received', $request->all());

        if ($request->filled('phone_number')) {
            $user = User::whereHas('profile', function ($query) use ($request) {
                $query->where('phone_number', $request->input('phone_number'));
            })->update([
                        'password' => Hash::make($request->input('password'))
                    ]);
        }
        if ($request->filled('otp')) {
            $otpCode = OtpCode::where('code', $request->input('otp'))
                ->where('type', TypeEnum::PHONE)
                ->where('purpose', PurposeEnum::FORGOT_PASSWORD)
                ->where('status', StatusEnum::VERIFIED)
                ->where('expires_at', '>=', now())
                ->firstOrFail();

            $otpCode->user->update([
                'password' => Hash::make($request->input('password'))
            ]);

            $otpCode->delete();
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
