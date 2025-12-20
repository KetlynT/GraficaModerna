<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpj; // Já existe no seu projeto PHP

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Público
    }

    public function rules(): array
    {
        return [
            'fullName' => 'required|string|min:3|max:100', // [StringLength(100, MinimumLength = 3)]
            'email'    => 'required|email:rfc,dns|unique:users,email', // [EmailAddress], unique
            'password' => 'required|string|min:6|confirmed', // [MinLength(6)], Confirmed exige field "password_confirmation"
            
            // Validação customizada CPF/CNPJ (igual ao seu validador C#)
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