<?php

namespace App\Http\Requests\Api\v1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'exists:profiles,phone_number'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.exists' => 'We could not find a user with that phone number.',
        ];
    }
}
