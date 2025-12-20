<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\ContentService;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Requests\Order\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    protected OrderService $orderService;
    protected ContentService $contentService;

    public function __construct(OrderService $orderService, ContentService $contentService)
    {
        // Auth middleware via routes
        $this->orderService = $orderService;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();

        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            abort(
                403,
                'Novos pedidos estão desativados temporariamente. Entre em contato para orçamento.'
            );
        }
    }

    /**
     * POST /api/orders (Checkout)
     */
    public function checkout(CheckoutRequest $request)
    {
        $this->checkPurchaseEnabled();

        // Validação complexa aninhada via CheckoutRequest
        $validated = $request->validated();

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        $order = $this->orderService->createOrderFromCart(
            $userId,
            $validated['address'],
            $validated['couponCode'] ?? null,
            $validated['shippingMethod']
        );

        return response()->json($order);
    }

    /**
     * GET /api/orders
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        $page = (int) $request->query('page', 1);
        $pageSize = (int) $request->query('pageSize', 10);

        $orders = $this->orderService->getUserOrders($userId, $page, $pageSize);
        return response()->json($orders);
    }

    /**
     * POST /api/orders/{id}/request-refund
     */
    public function requestRefund(RefundRequest $request, string $id)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        // Validação condicional via RefundRequest
        $validated = $request->validated();

        $this->orderService->requestRefund($id, $userId, $validated);
        return response()->json();
    }
}