<?php

namespace App\Http\Controllers;

use App\Services\CouponService;
use App\Services\ContentService;
use App\Http\Resources\CouponResource; // Para create/getall
use Illuminate\Http\Request;

class CouponsController extends Controller
{
    protected CouponService $service;
    protected ContentService $contentService;

    public function __construct(CouponService $service, ContentService $contentService)
    {
        $this->service = $service;
        $this->contentService = $contentService;
    }

    // GET api/coupons/validate/{code}
    public function validateCode(string $code)
    {
        // Lógica de Settings igual ao C#
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            return response()->json('Uso de cupons indisponível temporariamente.', 400);
        }

        $coupon = $this->service->getValidCoupon($code);

        if (!$coupon) {
            return response()->json('Cupom inválido ou expirado.', 404);
        }

        // Retorna objeto anônimo específico exigido pelo Frontend:
        // C#: return Ok(new { coupon.Code, coupon.DiscountPercentage });
        return response()->json([
            'code' => $coupon->code,
            'discountPercentage' => (float) $coupon->discount_percentage
        ]);
    }

    // Métodos CRUD Admin (implícitos no C# Service mas não mostrados no Controller enviado, 
    // mas se existirem rotas de admin, usariam o Resource):
    /*
    public function index() {
        return CouponResource::collection($this->service->getAll());
    }
    */
}