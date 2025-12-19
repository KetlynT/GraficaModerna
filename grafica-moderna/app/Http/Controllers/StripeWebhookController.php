<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderHistory;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Assinatura Inválida'], 400);
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $this->fulfillOrder($session);
        }

        return response()->json(['status' => 'success']);
    }

    private function fulfillOrder($session)
    {
        $orderId = $session->client_reference_id;
        $order = Order::find($orderId);

        if ($order && $order->status === 'Pendente') {
            $order->update([
                'status' => 'Aprovado',
                'stripe_payment_intent_id' => $session->payment_intent
            ]);

            OrderHistory::create([
                'order_id' => $order->id,
                'status' => 'Aprovado',
                'message' => 'Pagamento confirmado via Stripe Webhook',
                'changed_by' => 'System'
            ]);
            
            // Aqui você dispararia o e-mail de confirmação
            // Mail::to($order->user->email)->send(new PaymentConfirmed($order));
        }
    }
}