<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpj;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Requer auth, mas o middleware resolve isso
    }

    public function rules(): array
    {
        // Ignora o ID do usuário atual na validação de unique se necessário
        // $userId = $this->user()->id; 

        return [
            'fullName'    => 'required|string|min:3|max:100',
            'phoneNumber' => 'nullable|string|max:20',
            'cpfCnpj'     => ['required', 'string', new CpfCnpj()],
        ];
    }
}