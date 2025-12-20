<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'Admin';
    }

    public function rules(): array
    {
        $slugUnique = Rule::unique('content_pages', 'slug');
        
        // Se for update (tem slug na rota ou id), ignora o atual
        if ($this->route('slug')) {
             $slugUnique->ignore($this->route('slug'), 'slug');
        }

        return [
            // C# Matches("^[a-z0-9-]+$")
            'slug' => [
                'required', 
                'string', 
                'regex:/^[a-z0-9-]+$/', 
                $slugUnique
            ],
            
            // C# MaxLength(200)
            'title' => 'required|string|max:200',
            'content' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens (ex: minha-pagina).',
        ];
    }
}