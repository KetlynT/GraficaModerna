<?php

namespace App\Http\Controllers;

use App\Services\Shipping\MelhorEnvioService;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller
{
    protected $shippingService;

    public function __construct(MelhorEnvioService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'zipCode' => 'required|string|min:8'
        ]);

        $zipCode = preg_replace('/[^0-9]/', '', $request->input('zipCode'));
        $userId = Auth::id();

        // Busca itens do carrinho atual do usuário
        $cart = Cart::with('items.product')->where('user_id', $userId)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([], 200);
        }

        try {
            // Passa os itens do Model Eloquent para o Service
            $options = $this->shippingService->calculateShipping($zipCode, $cart->items->all());
            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}