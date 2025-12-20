<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentsController extends Controller
{
    protected $paymentService;

    public function __construct(StripePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function createCheckoutSession(Request $request)
    {
        $request->validate(['orderId' => 'required|uuid']);
        
        $order = Order::where('id', $request->orderId)
                      ->where('user_id', Auth::id())
                      ->firstOrFail();

        if ($order->status === 'Pago') {
            return response()->json(['error' => 'Pedido já pago.'], 400);
        }

        try {
            $url = $this->paymentService->createCheckoutSession($order);
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}