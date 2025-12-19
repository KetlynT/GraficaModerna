<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use Stripe\Stripe;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function handle(Request $request)
    {
        // Configurar a chave secreta do Webhook (obtenha no dashboard do Stripe)
        $endpointSecret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Processar Evento
        if ($event->type == 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            
            // O ID do pedido deve ter sido passado no metadata ao criar a sessão
            $orderId = $paymentIntent->metadata->order_id ?? null;
            $amount = $paymentIntent->amount_received; // em centavos
            $transactionId = $paymentIntent->id;

            if ($orderId) {
                try {
                    $this->orderService->confirmPaymentViaWebhook($orderId, $transactionId, $amount);
                } catch (\Exception $e) {
                    Log::error("Erro ao processar webhook do pedido $orderId: " . $e->getMessage());
                    return response()->json(['error' => 'Internal Server Error'], 500);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}