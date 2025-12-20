<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mapeamento exato do CartItemDto.cs
        // Garante que o frontend receba 'productImage' (string) e dimensões soltas
        return [
            'id' => $this->id,
            'productId' => $this->product_id,
            'productName' => $this->product->name,
            'productImage' => $this->product->image_urls[0] ?? "", // Pega a primeira imagem ou vazio
            'unitPrice' => (float) $this->product->price,
            'quantity' => (int) $this->quantity,
            'totalPrice' => (float) ($this->product->price * $this->quantity),
            'weight' => (float) $this->product->weight,
            'width' => (int) $this->product->width,
            'height' => (int) $this->product->height,
            'length' => (int) $this->product->length,
        ];
    }
}