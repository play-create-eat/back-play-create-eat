<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
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
     *             required={"otp"},
     *             @OA\Property(property="otp", type="string", description="The OTP code", example="123456")
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
            'otp' => ['required', 'string'],
        ]);

//        $user = $request->user();
//
//        $otp = OtpCode::where('code', $request->otp)
//            ->where('user_id', $user->id)
//            ->whereNull('expired_at')
//            ->first();

        if ($request->input('otp') !== '1234') {
            return response()->json(['message' => 'Invalid OTP code.'], 400);
        }

//        $otp->update(['expired_at' => now()]);

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
    public function resend(Request $request)
    {
//        $user = $request->user();
//
//        $otp = OtpCode::where('user_id', $user->id)
//            ->whereNull('expired_at')
//            ->first();
//
//        if ($otp) {
//            return response()->json(['message' => 'OTP code already sent.'], 400);
//        }
//
//        $otp = OtpCode::create([
//            'user_id' => $user->id,
//            'code' => 1234,
//        ]);

        return response()->json(['message' => 'OTP code sent successfully.']);
    }
}
