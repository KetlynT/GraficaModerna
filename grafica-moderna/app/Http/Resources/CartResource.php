<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mapeamento exato do CartDto.cs
        $items = CartItemResource::collection($this->items);
        
        // Calcula o total somando os itens já processados pelo Resource
        // (No C# é feito: itemsDto.Sum(i => i.TotalPrice))
        $grandTotal = $this->items->reduce(function ($carry, $item) {
             return $carry + ($item->quantity * $item->product->price);
        }, 0);

        return [
            'id' => $this->id,
            'items' => $items,
            'grandTotal' => (float) $grandTotal
        ];
    }
}