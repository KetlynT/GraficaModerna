<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discountPercentage' => (float) $this->discount_percentage,
            'expiryDate' => $this->expiry_date,
            'isActive' => (bool) $this->is_active,
        ];
    }
}