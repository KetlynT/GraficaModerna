<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Verifica se é admin aqui ou deixa pro middleware de rota
        return $this->user() && $this->user()->role === 'Admin';
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|min:3|max:200',
            'description'   => 'required|string',
            'basePrice'     => 'required|numeric|min:0.01',
            'weight'        => 'required|numeric|min:0.01|max:30', // Limite correios ex: 30kg
            'width'         => 'required|numeric|min:1',
            'height'        => 'required|numeric|min:1',
            'length'        => 'required|numeric|min:1',
            'categoryId'    => 'required|string', // Se tiver tabela de categorias: 'exists:categories,id'
            'isActive'      => 'boolean',
            'images'        => 'nullable|array',
            'images.*'      => 'string|url', // Valida se cada item do array é URL
        ];
    }
}