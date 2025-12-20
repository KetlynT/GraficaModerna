<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'productId' => $this->product_id,
            'productName' => $this->product_name,
            'quantity' => (int) $this->quantity,
            'refundQuantity' => (int) $this->refund_quantity,
            'unitPrice' => (float) $this->unit_price,
            'total' => (float) ($this->quantity * $this->unit_price),
        ];
    }
}