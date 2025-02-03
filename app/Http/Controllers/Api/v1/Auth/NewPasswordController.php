<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NewPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $otp = OtpCode::where('code', $request->input('otp'))
            ->where('expires_at', '>=', now())
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Invalid otp.'], 422);
        }

        $otp->user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
