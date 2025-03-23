<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Database\Query\Builder;
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
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'child_id' => [
                'required',
                Rule::exists('children', 'id'),
            ],
            'product_id' => [
                'required',
                Rule::exists('products')->where(function (Builder $query, mixed $value) {
                    $query->available()->where('id', $value);
                }),
            ],
        ];
    }
}
