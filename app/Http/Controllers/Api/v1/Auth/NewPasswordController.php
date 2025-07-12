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
use Illuminate\Support\Facades\Log;
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

        Log::info('New password request received', $request->all());

        $user = User::whereHas('profile', function ($query) use ($request) {
            $query->where('phone_number', $request->input('phone_number'));
        })->first();

        if (!$user) {
            Log::info('User not found');
            return response()->json([
                'message' => 'Invalid credentials provided.'
            ], 422);
        }

        Log::info('User found', ['user' => $user]);

        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        Log::info('Password updated', ['user' => $user]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
