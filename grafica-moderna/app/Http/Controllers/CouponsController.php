<?php

namespace App\Http\Controllers;

use App\Services\CouponService;
use App\Services\ContentService;

class CouponsController extends Controller
{
    protected CouponService $service;
    protected ContentService $contentService;

    public function __construct(CouponService $service, ContentService $contentService)
    {
        $this->service = $service;
        $this->contentService = $contentService;
    }

    public function validateCode(string $code)
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            return response()->json(
                ['message' => 'Uso de cupons indisponível temporariamente.'],
                400
            );
        }

        $coupon = $this->service->getValidCoupon($code);

        if (!$coupon) {
            return response()->json(
                ['message' => 'Cupom inválido ou expirado.'],
                404
            );
        }

        return response()->json([
            'code' => $coupon->code,
            'discountPercentage' => $coupon->discount_percentage,
        ]);
    }
}