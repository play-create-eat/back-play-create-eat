<?php

namespace App\Enums\Otps;

enum PurposeEnum: string
{
    case REGISTER = 'register';
    case LOGIN = 'login';
    case FORGOT_PASSWORD = 'forgot_password';
    case CHANGE_EMAIL = 'change_email';
    case CHANGE_PHONE = 'change_phone';
    case CHANGE_PASSWORD = 'change_password';
    case Verify_EMAIL = 'verify_email';
    case Verify_PHONE = 'verify_phone';

    public static function values(): array
    {
        return array_map(fn($purpose) => $purpose->value, self::cases());
    }
}
