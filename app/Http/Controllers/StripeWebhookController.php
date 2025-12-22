<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\MetadataSecurityService;
use App\Models\ProcessedWebhookEvent;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Exception;

class StripeWebhookController extends Controller
{
    protected OrderService $orderService;
    protected MetadataSecurityService $securityService;

    public function __construct(
        OrderService $orderService,
        MetadataSecurityService $securityService
    ) {
        $this->orderService = $orderService;
        $this->securityService = $securityService;
    }

    public function handleStripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        if (!$endpointSecret) {
            Log::critical('STRIPE_WEBHOOK_SECRET não configurado.');
            return response()->json(['message' => 'Configuração inválida'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $endpointSecret);

            if (ProcessedWebhookEvent::where('event_id', $event->id)->exists()) {
                Log::info("[Webhook] Evento {$event->id} já processado. Ignorando.");
                return response()->json(null, 200);
            }

            switch ($event->type) {
                case 'checkout.session.completed':
                case 'checkout.session.async_payment_succeeded':
                    $this->processCheckoutSession($event);
                    break;

                case 'checkout.session.async_payment_failed':
                case 'payment_intent.payment_failed':
                    Log::warning("[Webhook] Pagamento falhou: {$event->id}");
                    break;

                default:
                    Log::info("[Webhook] Evento não monitorado: {$event->type}");
            }

            try {
                ProcessedWebhookEvent::create(['event_id' => $event->id]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '23000') return response()->json(null, 200);
                throw $e;
            }

            return response()->json(null, 200);

        } catch (SignatureVerificationException $e) {
            Log::error('[Webhook] Assinatura Stripe inválida.');
            return response()->json('Invalid signature', 400);
        } catch (\Throwable $e) {
            Log::error('[Webhook] Erro interno:', ['error' => $e->getMessage()]);
            return response()->json('Internal Server Error', 500);
        }
    }

    protected function processCheckoutSession($event): void
    {
        $session = $event->data->object;
        $metadata = $session->metadata; 
        
        $orderId = null;

        if (isset($metadata['order_data'])) {
            try {
                $orderId = $this->securityService->unprotect($metadata['order_data']);
            } catch (Exception $e) {
                Log::error("[Webhook] Falha ao descriptografar order_data: " . $e->getMessage());
            }
        }

        if (!$orderId) {
            Log::warning("[Webhook] Metadados falharam. Tentando busca por Session ID.");
            $order = Order::where('stripe_session_id', $session->id)->first();
            if ($order) {
                $orderId = $order->id;
            } else {
                Log::error("[Webhook] Pedido não encontrado para Session {$session->id}");
                return;
            }
        }

        try {
            $transactionId = $session->payment_intent;
            $amountPaid = $session->amount_total ?? 0;

            if (!$transactionId) {
                Log::error("[Webhook] PaymentIntent ausente. Order {$orderId}");
                return;
            }

            Log::info("[Webhook] Processando pagamento Order {$orderId}, Transaction {$transactionId}");

            $this->orderService->confirmPaymentViaWebhook(
                $orderId,
                $transactionId,
                $amountPaid
            );

            Log::info("[Webhook] Sucesso Order {$orderId}");

        } catch (Exception $e) {
            Log::critical("[SECURITY ALERT] Falha ao processar webhook {$event->id}: " . $e->getMessage());
            throw $e; 
        }
    }
}