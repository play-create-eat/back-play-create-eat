<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\TypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\v1\Auth\ResetPasswordRequest;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TwilloService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    /**
     /**
     * @OA\Post(
     *     path="/api/v1/forgot-password",
     *     summary="Request password reset",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
      *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="Phone number associated with the user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
      *             @OA\Property(property="message", type="string", example="OTP sent successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", description="An object containing validation errors for specific fields")
     *         )
     *     )
     * )
     * @throws Exception
     */
    public function forgot(ForgotPasswordRequest $request, OtpService $otpService, TwilloService $twilloService)
    {

        $user = User::whereHas('profile', function ($query) use ($request) {
            $query->where('phone_number', $request->input('phone_number'));
        })->first();

        $otpCode = $otpService->generate($user, TypeEnum::PHONE, PurposeEnum::FORGOT_PASSWORD, $request->input('phone_number'));
        $otpService->send($otpCode, $twilloService);

        return response()->json(['message' => 'OTP send successfully.']);
    }

    /**
    /**
     * @OA\Post(
     *     path="/api/v1/reset-password",
     *     summary="Set new password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="otp", type="string", example="123456", description="OTP code received by the user"),
      *             @OA\Property(property="password", type="string", format="password", example="newpassword123", description="New password"),
      *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid OTP",
     *         @OA\JsonContent(
      *             @OA\Property(property="message", type="string", example="Invalid OTP."),
     *             @OA\Property(property="errors", type="object", description="An object containing validation errors for specific fields")
     *         )
     *     )
     * )
     */
    public function reset(ResetPasswordRequest $request)
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
