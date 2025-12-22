<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'imageUrls' => $this->image_urls ?? [],
            'weight' => (float) $this->weight,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'length' => (int) $this->length,
            'stockQuantity' => (int) $this->stock_quantity,
        ];
    }
}