<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refundType' => ['required', 'string', Rule::in(['Total', 'Parcial'])],

            'items' => 'array|required_if:refundType,Parcial',

            'items.*.productId' => 'required|uuid',
            
            'items.*.quantity'  => 'required|integer|min:1|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'refundType.in' => 'O tipo de reembolso deve ser Total ou Parcial.',
            'items.required_if' => 'Para reembolso parcial, é necessário selecionar pelo menos um item.',
        ];
    }
}