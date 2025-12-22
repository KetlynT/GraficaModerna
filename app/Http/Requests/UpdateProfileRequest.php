<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpj;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullName'    => 'required|string|min:3|max:100',
            'phoneNumber' => 'nullable|string|max:20',
            'cpfCnpj'     => ['required', 'string', new CpfCnpj()],
        ];
    }
}