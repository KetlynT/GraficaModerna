<?php

namespace App\Http\Requests\Order;

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
            // [Required] public string RefundType (Total ou Parcial)
            'refundType' => ['required', 'string', Rule::in(['Total', 'Parcial'])],

            // Lógica do C#: if (RefundType == "Parcial" && (Items == null || !Items.Any()))
            // Tradução Laravel: required_if:refundType,Parcial
            'items' => 'array|required_if:refundType,Parcial',

            // Validação dos itens de reembolso (RefundItemDto)
            'items.*.productId' => 'required|uuid',
            
            // [Range(1, 1000)]
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