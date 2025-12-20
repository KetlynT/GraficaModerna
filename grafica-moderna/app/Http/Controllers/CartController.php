<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\ContentService;
use App\Http\Requests\Cart\CartOrderRequest;
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
            abort(403, 'Funcionalidade de compra indisponível temporariamente.');
        }
    }

    public function index()
    {
        $this->checkPurchaseEnabled();

        try {
            return response()->json(
                $this->service->getCart(Auth::id())
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function addItem(CartOrderRequest $request)
    {
        $this->checkPurchaseEnabled();

        try {
            // Validação via CartOrderRequest
            $this->service->addItem(Auth::id(), $request->validated());
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function updateItem(CartOrderRequest $request, string $itemId)
    {
        $this->checkPurchaseEnabled();

        // Reutiliza CartOrderRequest, mas focamos na quantidade
        $data = $request->validated();

        try {
            $this->service->updateItemQuantity(Auth::id(), $itemId, $data['quantity']);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function removeItem(string $itemId)
    {
        $this->checkPurchaseEnabled();

        try {
            $this->service->removeItem(Auth::id(), $itemId);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function clear()
    {
        $this->checkPurchaseEnabled();

        try {
            $this->service->clearCart(Auth::id());
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}