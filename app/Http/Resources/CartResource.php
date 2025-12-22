<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = CartItemResource::collection($this->items);
        
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