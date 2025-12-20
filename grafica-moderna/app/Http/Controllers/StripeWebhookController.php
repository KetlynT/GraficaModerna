<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\MetadataSecurityService;
use App\Models\ProcessedWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

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
            return response()->json(null, 500);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $endpointSecret
            );

            if (ProcessedWebhookEvent::where('event_id', $event->id)->exists()) {
                Log::info("Evento {$event->id} já processado. Ignorando.");
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
                    Log::info("[Webhook] Evento não tratado: {$event->type} ({$event->id})");
            }

            try {
                ProcessedWebhookEvent::create([
                    'event_id' => $event->id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Race condition ao salvar webhook processado', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json(null, 200);
        }
        catch (SignatureVerificationException $e) {
            Log::error('[Webhook] Assinatura Stripe inválida.');
            return response()->json('Invalid signature', 400);
        }
        catch (\Throwable $e) {
            Log::error('[Webhook] Erro interno ao processar webhook.', [
                'exception' => $e
            ]);
            return response()->json('Internal server error', 500);
        }
    }

    protected function processCheckoutSession(Event $event): void
    {
        $session = $event->data->object;

        if (!$session || empty($session->metadata['order_data'])) {
            Log::warning("[Webhook] Metadados ausentes no evento {$event->id}");
            return;
        }

        try {
            $plainOrderId = $this->securityService->unprotect($session->metadata['order_data']);

            if (!\Ramsey\Uuid\Uuid::isValid($plainOrderId)) {
                throw new \Exception('Invalid Order ID format');
            }

            $transactionId = $session->payment_intent;
            $amountPaid = $session->amount_total ?? 0;

            if (!$transactionId) {
                Log::error("[Webhook] PaymentIntent ausente. Order {$plainOrderId}");
                return;
            }

            Log::info("[Webhook] Confirmando pagamento Order {$plainOrderId}, Transaction {$transactionId}");

            $this->orderService->confirmPaymentViaWebhook(
                $plainOrderId,
                $transactionId,
                $amountPaid
            );

            Log::info("[Webhook] Pagamento confirmado. Order {$plainOrderId}");
        }
        catch (\Exception $e) {
            Log::critical("[SECURITY ALERT] Violação de integridade no webhook {$event->id}");
            throw $e;
        }
        catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'FATAL') || str_contains($e->getMessage(), 'SECURITY')) {
                Log::critical("[SECURITY ALERT] Tentativa de fraude Order {$plainOrderId}", [
                    'exception' => $e
                ]);
                throw $e;
            }

            throw $e;
        }
    }
}