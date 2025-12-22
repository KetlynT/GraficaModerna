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
            'destinationCep' => 'required|string|size:8',

            'items' => 'required|array|min:1|max:50',

            'items.*.productId' => 'required|uuid|exists:products,id',
            
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