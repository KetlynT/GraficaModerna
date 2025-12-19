<?php

namespace App\Services;

use Stripe\StripeClient;
use App\Models\Order;
use Exception;

class StripePaymentService
{
    protected $stripe;

    public function __construct()
    {
        // Configure sua chave no .env (STRIPE_SECRET_KEY)
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
                    'unit_amount' => (int)($item->unit_price * 100), // Centavos
                ],
                'quantity' => $item->quantity,
            ];
        }

        // Adicionar Frete como item
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
        
        // Adicionar Desconto (Stripe lida melhor com cupons, mas aqui usamos linha negativa ou ajuste)
        // Simplificação: Se houver desconto, o subtotal dos itens acima já deve estar ajustado
        // ou usamos coupons do Stripe. Para manter igual ao C#, enviamos o valor final.
        // Nota: A implementação exata depende de como você quer mostrar no checkout do Stripe.

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'], // ou 'boleto', 'pix' se ativado no Stripe BR
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => config('app.frontend_url') . "/checkout/success?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => config('app.frontend_url') . "/carrinho",
            'client_reference_id' => $order->id,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id
            ]
        ]);

        // Salvar ID da sessão no pedido para referência
        $order->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }
}