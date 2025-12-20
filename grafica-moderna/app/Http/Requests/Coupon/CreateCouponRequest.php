<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class CreateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'Admin';
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:coupons,code',
            
            // C# Range(1, 100)
            'discountPercentage' => 'required|integer|min:1|max:100',
            
            // C# Range(1, 3650) - ValidityDays
            'validityDays' => 'required|integer|min:1|max:3650',
        ];
    }
}