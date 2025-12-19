<?php
namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderService;
    protected $contentService;

    public function __construct(OrderService $orderService, ContentService $contentService)
    {
        $this->orderService = $orderService;
        $this->contentService = $contentService;
    }

    // [HttpPost] Checkout
    public function checkout(Request $request)
    {
        // Validação (substitui o DTO)
        $validated = $request->validate([
            'address' => 'required|array',
            'coupon_code' => 'nullable|string',
            'shipping_method' => 'required|string'
        ]);

        // Lógica de "CheckPurchaseEnabled"
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            return response()->json(['error' => 'Novos pedidos estão desativados.'], 403);
        }

        try {
            $order = $this->orderService->createOrderFromCart(
                Auth::id(),
                $validated['address'],
                $validated['coupon_code'] ?? null,
                $validated['shipping_method']
            );
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // [HttpGet] GetMyOrders
    public function index(Request $request)
    {
        $perPage = $request->query('pageSize', 10);
        $orders = $this->orderService->getUserOrders(Auth::id(), $perPage);
        return response()->json($orders);
    }
}