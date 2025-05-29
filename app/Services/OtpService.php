<?php

namespace App\Services;

use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use App\Enums\Otps\TypeEnum;
use App\Models\OtpCode;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\configureScope;

class OtpService
{
    public function __construct(protected TwilloService $twilloService, protected MyInboxMediaService $myInboxMediaService)
    {
    }

    public function generate(?User $user, TypeEnum $type, PurposeEnum $purpose, string $identifier): OtpCode
    {
        $code = rand(1000, 9999);

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
    public function send(OtpCode $otpCode): OtpService
    {
        $message = "Your OTP is $otpCode->code. It will expire in 2 minutes.";

        if ($otpCode->type === TypeEnum::PHONE) {
            if (str_starts_with($otpCode->identifier, '+971')) {
                $result = $this->myInboxMediaService->sendSms($otpCode->identifier, $message);

                if (!$result['success']) {
                    $errorMessage = "Failed to send SMS via MyInboxMedia";
                    $errorContext = [
                        'otp_id' => $otpCode->id,
                        'identifier' => $this->maskPhoneNumber($otpCode->identifier),
                        'purpose' => $otpCode->purpose->value,
                        'error_details' => $result,
                        'service' => 'MyInboxMedia',
                    ];

                    if (function_exists('app') && app()->bound('sentry')) {
                        configureScope(function (Scope $scope) use ($errorContext) {
                            $scope->setContext('otp_sending_failure', $errorContext);
                            $scope->setTag('service', 'MyInboxMedia');
                            $scope->setTag('operation', 'send_otp');
                            $scope->setTag('error_code', $result['error_code'] ?? 'unknown');
                        });

                        captureMessage($errorMessage, Severity::error());
                    }

                    Log::error($errorMessage, $errorContext);

                    throw new Exception($errorMessage . ": " . ($result['error'] ?? 'Unknown error'));
                }

                Log::info('OTP SMS sent successfully via MyInboxMedia', [
                    'otp_id' => $otpCode->id,
                    'identifier' => $this->maskPhoneNumber($otpCode->identifier),
                    'message_id' => $result['message_id'] ?? null,
                    'response_time_ms' => $result['response_time_ms'] ?? null,
                ]);

            } else {
                $result = $this->twilloService->sendSms($otpCode->identifier, $message);

                if ($result !== true) {
                    $errorMessage = "Failed to send SMS via Twilio";
                    $errorContext = [
                        'otp_id' => $otpCode->id,
                        'identifier' => $this->maskPhoneNumber($otpCode->identifier),
                        'purpose' => $otpCode->purpose->value,
                        'error_details' => $result,
                        'service' => 'Twilio',
                    ];

                    if (function_exists('app') && app()->bound('sentry')) {
                        configureScope(function (Scope $scope) use ($errorContext) {
                            $scope->setContext('otp_sending_failure', $errorContext);
                            $scope->setTag('service', 'Twilio');
                            $scope->setTag('operation', 'send_otp');
                        });

                        captureMessage($errorMessage, Severity::error());
                    }

                    Log::error($errorMessage, $errorContext);

                    throw new Exception("$errorMessage: $result");
                }

                Log::info('OTP SMS sent successfully via Twilio', [
                    'otp_id' => $otpCode->id,
                    'identifier' => $this->maskPhoneNumber($otpCode->identifier),
                ]);
            }
        }

        if ($otpCode->type === TypeEnum::EMAIL) {
            try {
                Mail::raw("Your OTP is $otpCode->code", function ($message) use ($otpCode) {
                    $message->to($otpCode->identifier)->subject('Your OTP Code');
                });

                Log::info('OTP email sent successfully', [
                    'otp_id' => $otpCode->id,
                    'identifier' => $this->maskEmail($otpCode->identifier),
                ]);
            } catch (Exception $e) {
                $errorMessage = "Failed to send OTP email";
                $errorContext = [
                    'otp_id' => $otpCode->id,
                    'identifier' => $this->maskEmail($otpCode->identifier),
                    'purpose' => $otpCode->purpose->value,
                    'error' => $e->getMessage(),
                ];

                if (function_exists('app') && app()->bound('sentry')) {
                    configureScope(function (Scope $scope) use ($errorContext) {
                        $scope->setContext('otp_email_failure', $errorContext);
                        $scope->setTag('service', 'Email');
                        $scope->setTag('operation', 'send_otp');
                    });

                    captureException($e);
                }

                Log::error($errorMessage, $errorContext);
                throw new Exception("$errorMessage: {$e->getMessage()}");
            }
        }

        return $this;
    }

    /**
     * Mask phone number for privacy in logs
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '***';
        }

        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    /**
     * Mask email for privacy in logs
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = strlen($username) > 2
            ? substr($username, 0, 2) . str_repeat('*', strlen($username) - 2)
            : '***';

        return $maskedUsername . '@' . $domain;
    }
}
