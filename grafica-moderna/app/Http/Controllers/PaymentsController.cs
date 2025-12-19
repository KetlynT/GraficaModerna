<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StripePaymentService;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class PaymentsController extends Controller
{
    protected $paymentService;

    public function __construct(StripePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // [HttpPost("checkout")]
    // Cria a sessão no Stripe para um pedido específico
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'orderId' => 'required|uuid'
        ]);

        $user = Auth::user();
        $order = Order::where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Pedido não encontrado ou não pertence a este usuário.'], 404);
        }

        if ($order->status !== 'Pendente') {
            return response()->json(['message' => 'Este pedido não está pendente de pagamento.'], 400);
        }

        try {
            $sessionUrl = $this->paymentService->createCheckoutSession($order);
            return response()->json(['url' => $sessionUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}