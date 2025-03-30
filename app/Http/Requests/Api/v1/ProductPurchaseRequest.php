<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductPurchaseRequest extends FormRequest
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
            'child_id' => 'required|integer',
            'product_id' => 'required|integer',
            'date' => [
                'required',
                Rule::date()->afterOrEqual(today()),
            ],
            'loyalty_points_amount' => 'required|integer|min:0',
        ];
    }
}
