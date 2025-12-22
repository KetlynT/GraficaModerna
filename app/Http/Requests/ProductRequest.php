<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'Admin';
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|min:3|max:200',
            'description'   => 'required|string',
            'basePrice'     => 'required|numeric|min:0.01',
            'weight'        => 'required|numeric|min:0.01|max:30',
            'width'         => 'required|numeric|min:1',
            'height'        => 'required|numeric|min:1',
            'length'        => 'required|numeric|min:1',
            'categoryId'    => 'required|string',
            'isActive'      => 'boolean',
            'images'        => 'nullable|array',
            'images.*'      => 'string|url',
        ];
    }
}