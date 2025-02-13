<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use App\Enums\Otps\TypeEnum;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Services\OtpService;
use App\Services\TwilloService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/otp/verify",
     *     summary="Verify OTP code",
     *     tags={"OTP"},
     *     description="Verifies the OTP code for a user and marks the account as verified.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"identifier", "otp"},
     *              @OA\Property(property="identifier", type="string", description="The identifier for the OTP", example="user@example.com"),
     *              @OA\Property(property="otp", type="string", description="The OTP code", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account verified successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid OTP code",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid OTP code.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function verify(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'otp' => ['required', 'string'],
        ]);

        $otp = OtpCode::where('identifier', $request->input('identifier'))
            ->where('code', $request->input('otp'))
            ->where('expires_at', '>=', now())
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Invalid OTP code.'], 400);
        }

        $otp->update([
            'status' => StatusEnum::VERIFIED,
            'expires_at' => now()
        ]);

        $otp->delete();

        return response()->json(['message' => 'Account verified successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/otp/resend",
     *     summary="Resend OTP code",
     *     tags={"OTP"},
     *     description="Resends an OTP code to the authenticated user if no active OTP exists.",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="OTP code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP code sent successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP code already sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP code already sent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function resend(Request $request, OtpService $otpService, TwilloService $twilloService)
    {
        $request->validate([
            'identifier' => ['required']
        ]);

        $otps = OtpCode::where('identifier', $request->input('identifier'))
            ->where('expires_at', '>=',  now())->get();

        foreach ($otps as $otp) {
            $otp->delete();
        }

        $otp = $otpService->generate(null, TypeEnum::PHONE, PurposeEnum::REGISTER, $request->input('identifier'));
        $otpService->send($otp, $twilloService);

        return response()->json(['message' => 'OTP code sent successfully.']);
    }
}
