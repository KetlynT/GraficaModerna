<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\ContentService;
use App\Http\Resources\CartResource;
use App\Http\Requests\CartOrderRequest; // Nome do request no Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected CartService $service;
    protected ContentService $contentService;

    public function __construct(CartService $service, ContentService $contentService)
    {
        $this->service = $service;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            // Lança exceção para ser capturada pelo bloco try/catch e retornar 400
            throw new \Exception("Funcionalidade de compra indisponível temporariamente.");
        }
    }

    public function index()
    {
        try {
            $this->checkPurchaseEnabled();
            $cart = $this->service->getCart(Auth::id());
            return new CartResource($cart); // Retorna CartDto
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addItem(CartOrderRequest $request)
    {
        try {
            $this->checkPurchaseEnabled();
            $this->service->addItem(Auth::id(), $request->validated());
            return response()->json([], 200); // Ok()
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateItem(Request $request, string $itemId)
    {
        // O C# usa [FromBody] UpdateCartItemDto dto, que só tem Quantity
        // Aqui podemos validar inline ou criar um Request específico
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        try {
            $this->checkPurchaseEnabled();
            $this->service->updateItemQuantity(Auth::id(), $itemId, $request->input('quantity'));
            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function removeItem(string $itemId)
    {
        try {
            $this->checkPurchaseEnabled();
            $this->service->removeItem(Auth::id(), $itemId);
            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function clearCart()
    {
        try {
            $this->checkPurchaseEnabled();
            $this->service->clearCart(Auth::id());
            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}