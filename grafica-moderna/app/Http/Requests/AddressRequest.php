<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        // Limpeza de dados antes de validar (igual aos seus Setters no C# ou InputCleaner)
        if ($this->has('zipCode')) {
            $this->merge([
                'zipCode' => preg_replace('/\D/', '', $this->zipCode)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:50',     // Nome do local (Casa, Trabalho)
            'receiverName'  => 'required|string|max:100',
            'zipCode'       => 'required|string|size:8',     // CEP limpo tem 8 chars
            'street'        => 'required|string|max:200',
            'number'        => 'required|string|max:20',
            'neighborhood'  => 'required|string|max:100',
            'city'          => 'required|string|max:100',
            'state'         => 'required|string|size:2',     // UF
            'phoneNumber'   => 'required|string|max:20',     // DDD + Numero
            'complement'    => 'nullable|string|max:100',
            'reference'     => 'nullable|string|max:200',
            'isDefault'     => 'boolean',
        ];
    }
}