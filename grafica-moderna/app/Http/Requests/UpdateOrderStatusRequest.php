<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    'Pendente',
                    'Pago',
                    'Enviado',
                    'Entregue',
                    'Cancelado',
                    'Reembolso Solicitado',
                    'Aguardando Devolução',
                    'Reembolsado',
                    'Reembolsado Parcialmente',
                    'Reembolso Reprovado'
                ])
            ],
            // Campos opcionais para lógica de admin (Reembolso/Devolução)
            'trackingCode' => 'nullable|string',
            'reverseLogisticsCode' => 'nullable|string',
            'returnInstructions' => 'nullable|string',
            'refundRejectionReason' => 'nullable|string',
            'refundRejectionProof' => 'nullable|string',
            'refundAmount' => 'nullable|numeric|min:0'
        ];
    }
}