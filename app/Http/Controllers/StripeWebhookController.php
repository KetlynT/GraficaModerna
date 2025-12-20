<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\MetadataSecurityService;
use App\Models\ProcessedWebhookEvent;
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

            // Idempotência: Evita processar o mesmo evento duas vezes
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

            // Marca evento como processado
            try {
                ProcessedWebhookEvent::create(['event_id' => $event->id]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Se der erro ao salvar (race condition), apenas loga
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
        
        // Acesso aos metadados (depende da versão da lib do Stripe, geralmente array ou objeto)
        // No PHP SDK recente, $session->metadata é um StripeObject que age como array
        $metadata = $session->metadata; 
        
        if (!isset($metadata['order_data'])) {
            Log::warning("[Webhook] Metadados 'order_data' ausentes no evento {$event->id}");
            return;
        }

        try {
            // Descriptografa o ID do pedido
            $plainOrderId = $this->securityService->unprotect($metadata['order_data']);
            
            $transactionId = $session->payment_intent;
            $amountPaid = $session->amount_total ?? 0;

            if (!$transactionId) {
                Log::error("[Webhook] PaymentIntent ausente. Order {$plainOrderId}");
                return;
            }

            Log::info("[Webhook] Processando pagamento Order {$plainOrderId}, Transaction {$transactionId}");

            $this->orderService->confirmPaymentViaWebhook(
                $plainOrderId,
                $transactionId,
                $amountPaid
            );

            Log::info("[Webhook] Sucesso Order {$plainOrderId}");

        } catch (Exception $e) {
            Log::critical("[SECURITY ALERT] Falha ao processar webhook {$event->id}: " . $e->getMessage());
            throw $e; // Relança para o Stripe tentar de novo (500)
        }
    }
}