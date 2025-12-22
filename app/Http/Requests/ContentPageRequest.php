<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:200',
            'content' => 'required|string',
        ];

        if ($this->isMethod('post')) {
            $rules['slug'] = 'required|string|max:100|unique:content_pages,slug';
        }

        return $rules;
    }
}