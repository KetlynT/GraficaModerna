<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productId' => 'required|uuid|exists:products,id',

            'quantity'  => 'required|integer|min:1|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'A quantidade mínima é 1.',
            'quantity.max' => 'A quantidade máxima permitida por item é 1000.',
        ];
    }
}