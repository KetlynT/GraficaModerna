<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mapeia AddressDto.cs
        return [
            'id' => $this->id,
            'name' => $this->name,
            'receiverName' => $this->receiver_name,
            'zipCode' => $this->zip_code,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement ?? "",
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'reference' => $this->reference ?? "",
            'phoneNumber' => $this->phone_number,
            'isDefault' => (bool) $this->is_default,
        ];
    }
}