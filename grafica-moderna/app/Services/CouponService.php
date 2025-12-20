<?php

namespace App\Services;

use App\Models\Coupon;
use Carbon\Carbon;
use Exception;

class CouponService
{
    public function create(array $data)
    {
        // GetByCodeAsync check
        if (Coupon::where('code', $data['code'])->exists()) {
            throw new Exception("Cupom já existe.");
        }

        return Coupon::create([
            'code' => strtoupper(trim($data['code'])),
            'discount_percentage' => $data['discountPercentage'],
            'expiry_date' => Carbon::now()->addDays($data['validityDays']),
            'is_active' => true
        ]);
    }

    public function getAll()
    {
        return Coupon::all(); // GetAllAsync
    }

    public function delete(string $id)
    {
        Coupon::destroy($id); // DeleteAsync
    }

    public function getValidCoupon(string $code)
    {
        $coupon = Coupon::where('code', $code)->first();

        // O método isValid() deve estar no Model Coupon, igual ao C#
        if (!$coupon || !$coupon->isValid()) {
            return null;
        }

        return $coupon;
    }
}