<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mapeia EmailTemplateDto.cs
        return [
            'id' => $this->id,
            'key' => $this->key,
            'subject' => $this->subject,
            'bodyContent' => $this->body_content, // Snake para Camel
            'description' => $this->description,
            'updatedAt' => $this->updated_at,
        ];
    }
}