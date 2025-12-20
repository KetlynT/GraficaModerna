<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Limpa o CEP antes de validar, igual ao comportamento do C# 
     * que espera apenas dígitos ou limpa no setter.
     */
    protected function prepareForValidation()
    {
        if ($this->has('destinationCep')) {
            $this->merge([
                'destinationCep' => preg_replace('/\D/', '', $this->destinationCep)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // [Required, RegularExpression(@"^\d{8}$")]
            'destinationCep' => 'required|string|size:8',

            // [Required, MinLength(1), MaxLength(50)] - Lista de itens
            'items' => 'required|array|min:1|max:50',

            // Validação dos itens internos (ShippingItemDto)
            'items.*.productId' => 'required|uuid|exists:products,id',
            
            // [Range(1, 1000)]
            'items.*.quantity'  => 'required|integer|min:1|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'destinationCep.size' => 'O CEP deve conter exatamente 8 dígitos.',
            'items.max' => 'O cálculo de frete permite no máximo 50 itens distintos.',
        ];
    }
}