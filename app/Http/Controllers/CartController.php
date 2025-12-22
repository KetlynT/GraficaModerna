<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\ContentService;
use App\Http\Resources\CartResource;
use App\Http\Requests\CartOrderRequest;
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
            throw new \Exception("Funcionalidade de compra indisponível temporariamente.");
        }
    }

    public function index()
    {
        try {
            $this->checkPurchaseEnabled();
            $cart = $this->service->getCart(Auth::id());
            return new CartResource($cart);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addItem(CartOrderRequest $request)
    {
        try {
            $this->checkPurchaseEnabled();
            $this->service->addItem(Auth::id(), $request->validated());
            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = auth()->user()->cart;
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $item = $cart->items()->where('id', $id)->first(); 
    
        if (!$item) {
            $item = $cart->items()->where('product_id', $id)->first();
        }

        if (!$item) {
            return response()->json(['message' => 'Item not found in cart'], 404);
        }

        $item->quantity = $request->quantity;
        $item->save();
        return response()->json($cart->load('items.product'));
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