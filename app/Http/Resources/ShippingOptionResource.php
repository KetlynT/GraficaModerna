<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'price' => (float) $this->resource['price'],
            'deliveryDays' => (int) $this->resource['deliveryDays'],
            'provider' => $this->resource['provider'],
        ];
    }
}