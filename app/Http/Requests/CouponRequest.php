<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|min:3|max:20|unique:coupons,code',
            'discountPercentage' => 'required|numeric|min:1|max:100',
            'validityDays' => 'required|integer|min:1'
        ];
    }
}