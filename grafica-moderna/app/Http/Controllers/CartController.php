<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $service;
    protected $contentService;

    public function __construct(CartService $service, ContentService $contentService)
    {
        $this->service = $service;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled()
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            abort(403, "Compras temporariamente desativadas.");
        }
    }

    public function index()
    {
        try {
            $this->checkPurchaseEnabled();
            return response()->json($this->service->getCart(Auth::id()));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addItem(Request $request)
    {
        $this->checkPurchaseEnabled();
        $data = $request->validate([
            'productId' => 'required|uuid',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            $this->service->addItem(Auth::id(), $data);
            return response()->noContent(); // 204 OK
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateItem(Request $request, $itemId)
    {
        $this->checkPurchaseEnabled();
        $data = $request->validate(['quantity' => 'required|integer']);

        try {
            $this->service->updateItemQuantity(Auth::id(), $itemId, $data['quantity']);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function removeItem($itemId)
    {
        $this->checkPurchaseEnabled();
        $this->service->removeItem(Auth::id(), $itemId);
        return response()->noContent();
    }

    public function clear()
    {
        $this->checkPurchaseEnabled();
        $this->service->clearCart(Auth::id());
        return response()->noContent();
    }
}