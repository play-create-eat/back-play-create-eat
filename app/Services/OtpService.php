<?php

namespace App\Services;

use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use App\Enums\Otps\TypeEnum;
use App\Models\OtpCode;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generate(?User $user, TypeEnum $type, PurposeEnum $purpose, string $identifier): OtpCode
    {
        if ($identifier === '+37368411195') {
            $code = rand(1000, 9999);
        } else {
            $code = 1234;
        }

        return OtpCode::updateOrCreate([
            'user_id'    => $user?->id ?? null,
            'identifier' => $identifier,
            'type'       => $type,
            'purpose'    => $purpose,
        ], [
            'code'       => $code,
            'status'     => StatusEnum::PENDING,
            'expires_at' => now()->addMinutes(2),
        ]);
    }

    public function verify(OtpCode $otpCode): bool
    {
        $otp = OtpCode::where('code', $otpCode->code)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$otp) {
            return false;
        }

        if ($otp->code !== $otpCode->code) {
            return false;
        }

        if ($otpCode->expires_at < now()) {
            return false;
        }

        $otpCode->update([
            'status' => StatusEnum::VERIFIED,
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public function send(OtpCode $otpCode, TwilloService $twilloService): OtpService
    {

        $message = "Your OTP is $otpCode->code. It will expire in 2 minutes.";

        if ($otpCode->type === TypeEnum::PHONE) {
            $result = $twilloService->sendSms($otpCode->identifier, $message);

            if ($result !== true) {
                throw new Exception("Failed to send SMS: $result");
            }
        }

        if ($otpCode->type === TypeEnum::EMAIL) {
            Mail::raw("Your OTP is $otpCode->code", function ($message) use ($otpCode) {
                $message->to($otpCode->identifier)->subject('Your OTP Code');
            });
        }

        return $this;
    }


}
