<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mapeia UserProfileDto.cs
        return [
            'fullName' => $this->full_name,
            'email' => $this->email,
            'cpfCnpj' => $this->cpf_cnpj,
            'phoneNumber' => $this->phone_number ?? "",
        ];
    }
}