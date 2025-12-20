<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\ContentService;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\RefundRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrdersController extends Controller
{
    protected OrderService $orderService;
    protected ContentService $contentService;

    public function __construct(OrderService $orderService, ContentService $contentService)
    {
        $this->orderService = $orderService;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings(); // Adapte se seu ContentService retornar array direto
        // Em PHP array 'false' string pode ser tricky, verifique se vem booleano ou string
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
             // throw new Exception igual ao C# InvalidOperationException, que o Handler transforma em erro
             abort(403, "Novos pedidos estão desativados temporariamente. Entre em contato para orçamento.");
        }
    }

    public function checkout(CheckoutRequest $request)
    {
        $this->checkPurchaseEnabled();

        $validated = $request->validated();
        $userId = Auth::id();

        $order = $this->orderService->createOrderFromCart(
            $userId,
            $validated['address'], // Certifique-se que o Request valida AddressDto structure
            $validated['couponCode'] ?? null,
            $validated['shippingMethod']
        );

        // Retorna usando o Resource para garantir camelCase e estrutura correta
        return new OrderResource($order);
    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        $page = (int) $request->query('page', 1);
        $pageSize = (int) $request->query('pageSize', 10);

        $paginator = $this->orderService->getUserOrders($userId, $page, $pageSize);

        // Resource::collection lida com o paginator automaticamente (meta, links, data)
        return OrderResource::collection($paginator);
    }

    public function requestRefund(RefundRequest $request, string $id)
    {
        $userId = Auth::id();
        $validated = $request->validated();

        $this->orderService->requestRefund($id, $userId, $validated);

        return response()->json([], 200);
    }

    public function show(string $id)
    {
        try {
            $userId = Auth::id();
            $order = $this->orderService->getOrderById($id, $userId);
            
            return new OrderResource($order);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }
    }
}