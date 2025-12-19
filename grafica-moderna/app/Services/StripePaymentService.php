<?php

namespace App\Services;

use Stripe\StripeClient;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Order $order)
    {
        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'brl',
                    'product_data' => ['name' => $item->product_name],
                    'unit_amount' => (int)($item->unit_price * 100),
                ],
                'quantity' => $item->quantity,
            ];
        }

        if ($order->shipping_cost > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'brl',
                    'product_data' => ['name' => 'Frete - ' . $order->shipping_method],
                    'unit_amount' => (int)($order->shipping_cost * 100),
                ],
                'quantity' => 1,
            ];
        }

        // Se houver desconto, o Stripe idealmente usaria cupons, 
        // mas para manter consistência com o valor final calculado no backend:
        // (Nota: O Stripe Checkout não aceita itens com valor negativo diretamente, 
        // então o desconto geralmente é aplicado via 'discounts' ou ajustando o unit_amount dos itens. 
        // Para simplificar a migração, assumimos que o createOrder já calculou o total correto ou usamos coupons do Stripe).
        
        $sessionPayload = [
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => config('app.frontend_url') . "/checkout/success?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => config('app.frontend_url') . "/carrinho",
            'client_reference_id' => $order->id,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id
            ]
        ];

        // Se tiver cupom aplicado, tentar passar via metadata ou lógica de desconto do Stripe
        // Aqui mantemos simples conforme o original

        $session = $this->stripe->checkout->sessions->create($sessionPayload);

        $order->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    /**
     * Realiza o estorno (Total ou Parcial)
     */
    public function refund(string $paymentIntentId, ?float $amount = null)
    {
        try {
            $params = ['payment_intent' => $paymentIntentId];
            
            // Se amount for passado, é reembolso parcial (valor em centavos)
            if ($amount !== null && $amount > 0) {
                $params['amount'] = (int)($amount * 100);
            }

            $this->stripe->refunds->create($params);
            
        } catch (Exception $e) {
            Log::error("Erro no Reembolso Stripe: " . $e->getMessage());
            throw new Exception("Falha ao processar reembolso no gateway de pagamento: " . $e->getMessage());
        }
    }
}