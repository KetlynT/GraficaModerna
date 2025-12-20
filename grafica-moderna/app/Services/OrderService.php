<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Services\Shipping\MelhorEnvioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class OrderService
{
    protected $shippingService;
    protected $couponService;
    protected $emailService;
    protected $paymentService;

    public function __construct(
        MelhorEnvioService $shippingService,
        CouponService $couponService,
        EmailService $emailService,
        StripePaymentService $paymentService
    ) {
        $this->shippingService = $shippingService;
        $this->couponService = $couponService;
        $this->emailService = $emailService;
        $this->paymentService = $paymentService;
    }

    public function createOrderFromCart(string $userId, array $addressData, ?string $couponCode, string $shippingMethod)
    {
        // 1. Validar Carrinho
        $cart = Cart::with('items.product')->where('user_id', $userId)->first();
        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception("Carrinho vazio.");
        }

        // 2. Calcular Frete (Revalidação de segurança)
        // Nota: Em produção, você deve checar se o valor bate com o que veio do front
        $shippingOptions = $this->shippingService->calculateShipping($addressData['zipCode'], $cart->items->all());
        
        $selectedOption = collect($shippingOptions)->first(function ($opt) use ($shippingMethod) {
            return strcasecmp($opt['name'], $shippingMethod) === 0 || str_contains($opt['name'], $shippingMethod);
        });

        if (!$selectedOption) {
            // Fallback para ambiente de desenvolvimento ou erro
            if (config('app.env') === 'local') {
                $selectedOption = ['name' => $shippingMethod, 'price' => 20.00]; 
            } else {
                throw new Exception("Método de envio inválido ou indisponível.");
            }
        }
        
        $shippingCost = $selectedOption['price'];

        // 3. Iniciar Transação
        return DB::transaction(function () use ($userId, $cart, $addressData, $couponCode, $shippingMethod, $shippingCost, $selectedOption) {
            
            $subTotal = 0;
            $itemsToCreate = [];

            // Validação de Estoque Inicial
            foreach ($cart->items as $item) {
                if (!$item->product || !$item->product->is_active) continue;

                if ($item->product->stock_quantity < $item->quantity) {
                    throw new Exception("Estoque insuficiente para o produto: " . $item->product->name);
                }

                $subTotal += $item->quantity * $item->product->price;
                $itemsToCreate[] = $item;
            }

            // 4. Aplicar Cupom
            $discount = 0;
            if ($couponCode) {
                $coupon = $this->couponService->getValidCoupon($couponCode);
                if ($coupon) {
                    // TODO: Verificar se usuário já usou este cupom (Lógica do C#)
                    $discount = $subTotal * ($coupon->discount_percentage / 100);
                }
            }

            $totalAmount = $subTotal - $discount + $shippingCost;

            if ($totalAmount < 1.00) throw new Exception("Valor mínimo do pedido não atingido.");

            // Formatar endereço numa string única (compatibilidade com legado/C#)
            $formattedAddress = "{$addressData['street']}, {$addressData['number']} - {$addressData['neighborhood']}, {$addressData['city']}/{$addressData['state']} - CEP: {$addressData['zipCode']}";

            // 5. Criar Pedido
            $order = Order::create([
                'user_id' => $userId,
                'shipping_address' => $formattedAddress,
                'shipping_zip_code' => $addressData['zipCode'],
                'shipping_cost' => $shippingCost,
                'shipping_method' => $selectedOption['name'],
                'status' => 'Pendente',
                'order_date' => now(),
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'applied_coupon' => $couponCode ? strtoupper($couponCode) : null,
                'customer_ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // 6. Criar Itens do Pedido
            foreach ($itemsToCreate as $cartItem) {
                $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->price
                ]);
            }

            // 7. Registrar Histórico Inicial
            $order->history()->create([
                'status' => 'Pendente',
                'message' => 'Pedido criado via Checkout',
                'changed_by' => $userId, // UUID do usuário
                'timestamp' => now()
            ]);

            // 8. Registrar Uso do Cupom
            if ($discount > 0 && $couponCode) {
                CouponUsage::create([
                    'user_id' => $userId,
                    'coupon_code' => strtoupper($couponCode),
                    'order_id' => $order->id,
                    'used_at' => now()
                ]);
            }

            // 9. Limpar Carrinho
            $cart->items()->delete();
            $cart->touch();

            // 10. Enviar Email (Assíncrono na fila seria ideal)
            $user = $order->user;
            if ($user) {
                $this->emailService->send($user->email, 'OrderReceived', [
                    'name' => $user->full_name,
                    'orderNumber' => $order->id,
                    'total' => number_format($order->total_amount, 2, ',', '.')
                ]);
            }

            return $order;
        });
    }

    public function getUserOrders($userId, $perPage = 10)
    {
        return Order::with(['items', 'history'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // ==========================================
    // LÓGICA CRÍTICA DE PAGAMENTO (WEBHOOK)
    // ==========================================
    public function confirmPaymentViaWebhook(string $orderId, string $transactionId, int $amountPaidInCents)
    {
        $order = Order::with('items')->find($orderId);

        if (!$order) {
            Log::error("[Webhook] Pedido não encontrado: $orderId");
            return;
        }

        if ($order->status === 'Pago') {
            return; // Já processado
        }

        // Validação de Segurança (Valor pago vs Valor do pedido)
        $expectedAmount = (int)($order->total_amount * 100);
        if ($expectedAmount !== $amountPaidInCents) {
            Log::critical("[SECURITY] Divergência de valor. Pedido: $orderId. Esperado: $expectedAmount, Recebido: $amountPaidInCents");
            // Aqui você chamaria o envio de email de segurança igual no C#
            return;
        }

        DB::transaction(function () use ($order, $transactionId) {
            // Bloqueia produtos para leitura (evita Race Condition)
            $outOfStockItems = [];
            
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                
                if (!$product || $product->stock_quantity < $item->quantity) {
                    $outOfStockItems[] = $item->product_name;
                }
            }

            // SE FALTOU ESTOQUE NO MOMENTO DO PAGAMENTO:
            if (count($outOfStockItems) > 0) {
                Log::warning("[Webhook] Estoque insuficiente pós-pagamento. Iniciando estorno automático.");
                
                // 1. Estornar no Stripe (implementar método no PaymentService)
                // $this->paymentService->refund($transactionId);

                // 2. Cancelar Pedido
                $order->update([
                    'status' => 'Cancelado',
                    'stripe_payment_intent_id' => $transactionId
                ]);

                $order->history()->create([
                    'status' => 'Cancelado',
                    'message' => 'Cancelamento Automático: Estoque insuficiente. Valor estornado.',
                    'changed_by' => 'SYSTEM'
                ]);
                
                // 3. Enviar Email de Cancelamento
                $this->emailService->send($order->user->email, 'OrderCancelledOutOfStock', [
                    'name' => $order->user->full_name,
                    'items' => implode(', ', $outOfStockItems)
                ]);

                return;
            }

            // SUCESSO: Debitar Estoque
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                $product->decrement('stock_quantity', $item->quantity);
            }

            $order->update([
                'status' => 'Pago',
                'stripe_payment_intent_id' => $transactionId
            ]);

            $order->history()->create([
                'status' => 'Pago',
                'message' => "Pagamento confirmado via Webhook. ID: $transactionId",
                'changed_by' => 'StripeWebhook'
            ]);

            $this->emailService->send($order->user->email, 'PaymentConfirmed', [
                'name' => $order->user->full_name,
                'orderNumber' => $order->id
            ]);
        });
    }
    public function updateOrderStatus(string $orderId, string $newStatus, string $adminUserId, array $data = [])
    {
        $order = Order::findOrFail($orderId);
        $oldStatus = $order->status;
        $auditMessage = "Status alterado manualmente para {$newStatus}";

        DB::transaction(function () use ($order, $newStatus, $oldStatus, $adminUserId, $data, &$auditMessage) {
            
            // Lógica para Aguardando Devolução
            if ($newStatus === 'Aguardando Devolução') {
                if (!empty($data['reverseLogisticsCode'])) {
                    $order->reverse_logistics_code = $data['reverseLogisticsCode'];
                }
                $order->return_instructions = $data['returnInstructions'] ?? "Instruções padrão de devolução...";
                $auditMessage .= ". Instruções geradas.";
            }

            // Lógica para Rejeição de Reembolso
            if (in_array($newStatus, ['Reembolso Reprovado', 'Reembolsado', 'Cancelado'])) {
                if (!empty($data['refundRejectionReason'])) {
                    $order->refund_rejection_reason = $data['refundRejectionReason'];
                }
                if ($newStatus === 'Reembolso Reprovado') {
                    $auditMessage .= ". Justificativa anexada.";
                }
            }

            // Lógica de Reembolso Stripe
            if (in_array($newStatus, ['Reembolsado', 'Reembolsado Parcialmente', 'Cancelado']) 
                && !in_array($oldStatus, ['Reembolsado', 'Reembolsado Parcialmente', 'Cancelado'])
                && $order->stripe_payment_intent_id) {
                
                $amountToRefund = $data['refundAmount'] ?? ($order->refund_requested_amount ?? $order->total_amount);

                // Validações de valor
                if ($amountToRefund > $order->total_amount) {
                    throw new Exception("O valor do reembolso não pode ser maior que o total do pedido.");
                }

                // Executa Reembolso no Stripe
                $this->paymentService->refund($order->stripe_payment_intent_id, (float)$amountToRefund);
                
                $auditMessage .= ". Reembolso de R$ " . number_format($amountToRefund, 2, ',', '.') . " processado no Stripe.";

                // Ajusta status se for parcial
                if ($newStatus === 'Reembolsado' && $amountToRefund < $order->total_amount) {
                    $newStatus = 'Reembolsado Parcialmente';
                }
            }

            if ($newStatus === 'Entregue' && $oldStatus !== 'Entregue') {
                $order->delivery_date = now();
            }

            if (!empty($data['trackingCode'])) {
                $order->tracking_code = $data['trackingCode'];
                $auditMessage .= " (Rastreio: {$data['trackingCode']})";
            }

            $order->status = $newStatus;
            $order->save();

            // Log no Histórico
            $order->history()->create([
                'status' => $newStatus,
                'message' => $auditMessage,
                'changed_by' => "Admin:{$adminUserId}",
                'timestamp' => now()
            ]);

            // Envio de Email
            if ($oldStatus !== $newStatus) {
                $this->sendOrderUpdateEmail($order, $newStatus);
            }
        });
    }

    /**
     * Solicitação de Reembolso pelo Usuário
     */
    public function requestRefund(string $userId, string $orderId, array $data)
    {
        $order = Order::where('id', $orderId)->where('user_id', $userId)->firstOrFail();

        if (!in_array($order->status, ['Entregue', 'Pago'])) {
            throw new Exception("Status do pedido não permite solicitação de reembolso.");
        }

        if ($order->refund_type) {
            throw new Exception("Já existe uma solicitação de reembolso para este pedido.");
        }

        $refundType = $data['refundType'] ?? 'Total';
        $calculatedRefundAmount = 0;

        DB::transaction(function () use ($order, $refundType, $data, $userId, &$calculatedRefundAmount) {
            if ($refundType === 'Parcial') {
                if (empty($data['items'])) throw new Exception("Nenhum item selecionado para reembolso parcial.");

                $discountRatio = $order->sub_total > 0 ? $order->discount / $order->sub_total : 0;

                foreach ($data['items'] as $itemRequest) {
                    $orderItem = $order->items()->where('product_id', $itemRequest['productId'])->first();
                    
                    if (!$orderItem) throw new Exception("Produto não pertence ao pedido.");
                    if ($itemRequest['quantity'] > $orderItem->quantity) throw new Exception("Quantidade inválida.");

                    $orderItem->refund_quantity = $itemRequest['quantity'];
                    $orderItem->save();

                    $effectiveUnitPrice = $orderItem->unit_price * (1 - $discountRatio);
                    $calculatedRefundAmount += $effectiveUnitPrice * $itemRequest['quantity'];
                }

                $order->refund_type = 'Parcial';
                $order->refund_requested_amount = round($calculatedRefundAmount, 2);
                
                $message = "Cliente solicitou reembolso PARCIAL de R$ " . number_format($calculatedRefundAmount, 2);

            } else {
                $order->refund_type = 'Total';
                $order->refund_requested_amount = $order->total_amount;
                
                // Marca todos os itens
                foreach ($order->items as $item) {
                    $item->refund_quantity = $item->quantity;
                    $item->save();
                }
                
                $message = "Cliente solicitou reembolso TOTAL.";
            }

            $order->status = 'Reembolso Solicitado'; // Ou manter status e usar flag
            $order->save();

            $order->history()->create([
                'status' => 'Reembolso Solicitado',
                'message' => $message,
                'changed_by' => $userId,
                'timestamp' => now()
            ]);
        });
    }

    private function sendOrderUpdateEmail(Order $order, string $newStatus)
    {
        $templateMap = [
            'Pago' => 'PaymentConfirmed',
            'Enviado' => 'OrderShipped',
            'Entregue' => 'OrderDelivered',
            'Cancelado' => 'OrderCanceled',
            'Reembolsado' => 'OrderRefunded',
            'Reembolsado Parcialmente' => 'OrderPartiallyRefunded',
            'Aguardando Devolução' => 'OrderReturnInstructions',
            'Reembolso Reprovado' => 'OrderRefundRejected',
        ];

        if (!isset($templateMap[$newStatus]) || !$order->user) return;

        $this->emailService->send($order->user->email, $templateMap[$newStatus], [
            'name' => $order->user->full_name,
            'orderNumber' => $order->id,
            'trackingCode' => $order->tracking_code,
            'returnInstructions' => $order->return_instructions,
            'rejectionReason' => $order->refund_rejection_reason
        ]);
    }
}