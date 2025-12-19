<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected ContentService $contentService;

    public function __construct(OrderService $orderService, ContentService $contentService)
    {
        $this->middleware('auth:api');
        $this->orderService = $orderService;
        $this->contentService = $contentService;
    }

    /**
     * Verifica se compras estão habilitadas
     */
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
     * POST /api/orders
     * Checkout
     */
    public function checkout(Request $request)
    {
        $this->checkPurchaseEnabled();

        $validated = $request->validate([
            'address' => 'required|array',
            'couponCode' => 'nullable|string',
            'shippingMethod' => 'required|string',
        ]);

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
     * Lista pedidos do usuário autenticado (paginado)
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
    public function requestRefund(Request $request, string $id)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        $validated = $request->validate([
            'refundType' => 'required|in:Total,Parcial',
            'items' => 'required_if:refundType,Parcial|array',
            'items.*.productId' => 'required_with:items|uuid',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $this->orderService->requestRefund($id, $userId, $validated);
        return response()->json();
    }
}
