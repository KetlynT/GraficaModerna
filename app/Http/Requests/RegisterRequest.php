<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpj; 

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullName' => 'required|string|min:3|max:100',
            'email'    => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'cpfCnpj'  => ['required', 'string', new CpfCnpj()],
            'phoneNumber' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'As senhas não conferem.',
            'cpfCnpj.required' => 'O documento é obrigatório.',
        ];
    }
}