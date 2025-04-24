<?php

namespace App\Rules;

use App\Enums\IdTypeEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EmiratesIdNumber implements ValidationRule
{

    public function __construct(protected ?string $idType = null)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail("The $attribute must be a number.");
            return;
        }

        if ($this->idType === IdTypeEnum::EMIRATES->value) {
            if (strlen($value) !== 15) {
                $fail("The $attribute must be exactly 15 digits when ID type is Emirates.");
            }

            if (!str_starts_with($value, '784')) {
                $fail("The $attribute must start with 784 when ID type is Emirates.");
            }
        }
    }
}
