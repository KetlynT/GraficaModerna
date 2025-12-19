<?php

namespace App\Http\Controllers;

use App\Services\CouponService;
use App\Services\ContentService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    protected $service;
    protected $contentService;

    public function __construct(CouponService $service, ContentService $contentService)
    {
        $this->service = $service;
        $this->contentService = $contentService;
    }

    // [HttpGet("validate/{code}")]
    public function validateCode($code)
    {
        // Checagem de configurações (Feature Flag)
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            return response()->json(['message' => 'Uso de cupons indisponível temporariamente.'], 400);
        }

        $coupon = $this->service->getValidCoupon($code);
        if (!$coupon) {
            return response()->json(['message' => 'Cupom inválido ou expirado.'], 404);
        }

        return response()->json([
            'code' => $coupon->code,
            'discountPercentage' => $coupon->discount_percentage
        ]);
    }

    // Métodos administrativos (Create, Delete, GetAll) seriam adicionados aqui
    // seguindo a mesma lógica do seu código original, protegidos por middleware 'admin'
}