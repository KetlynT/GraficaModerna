<?php

namespace App\Http\Requests\Product;

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
            // C# Length(3, 100)
            'name' => 'required|string|min:3|max:100',
            
            // C# MaxLength(1000)
            'description' => 'required|string|max:1000',
            
            // C# GreaterThan(0)
            'basePrice' => 'required|numeric|gt:0',
            
            'weight' => 'required|numeric|min:0.01|max:30',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'length' => 'required|numeric|min:1',
            'categoryId' => 'required|string',
            'isActive' => 'boolean',
            'images' => 'nullable|array',
            
            // Validação Customizada de Extensões (ProductValidator.cs)
            'images.*' => [
                'string',
                'url',
                function ($attribute, $value, $fail) {
                    if (empty($value)) return;
                    
                    // Extrai extensão da URL
                    $path = parse_url($value, PHP_URL_PATH);
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov'];
                    
                    if (!in_array($extension, $allowed)) {
                        $fail("A URL {$value} possui uma extensão inválida. Formatos permitidos: jpg, png, webp, mp4, webm, mov.");
                    }
                }
            ],
        ];
    }
}