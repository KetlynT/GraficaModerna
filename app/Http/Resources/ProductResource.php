<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mapeia fielmente o ProductResponseDto.cs do C#
        // Garante que o frontend receba camelCase (stockQuantity) e não snake_case (stock_quantity)
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'imageUrls' => $this->image_urls ?? [], // Converte snake para camel
            'weight' => (float) $this->weight,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'length' => (int) $this->length,
            'stockQuantity' => (int) $this->stock_quantity, // Converte snake para camel
        ];
    }
}