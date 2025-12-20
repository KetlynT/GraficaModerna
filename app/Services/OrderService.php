<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Models\User;
use App\Services\MelhorEnvioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class OrderService
{
    protected MelhorEnvioService $shippingService;
    protected CouponService $couponService;
    protected EmailService $emailService;
    protected StripePaymentService $paymentService;

    const MIN_ORDER_AMOUNT = 1.00;
    const MAX_ORDER_AMOUNT = 100000.00;

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
        $cart = Cart::with('items.product')->where('user_id', $userId)->first();
        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception("Carrinho vazio.");
        }

        foreach ($cart->items as $i) {
            if ($i->quantity <= 0) throw new Exception("O carrinho contém itens com quantidades inválidas.");
        }

        $shippingOptions = $this->shippingService->calculate($addressData['zipCode'], $cart->items->all());
        
        $selectedOption = collect($shippingOptions)->first(function ($opt) use ($shippingMethod) {
            return strcasecmp(trim($opt['name']), trim($shippingMethod)) === 0;
        });

        if (!$selectedOption) {
            throw new Exception("Método de envio inválido ou indisponível.");
        }
        
        $shippingCost = (float) $selectedOption['price'];

        return DB::transaction(function () use ($userId, $cart, $addressData, $couponCode, $shippingMethod, $shippingCost, $selectedOption) {
            
            $subTotal = 0;
            $itemsToCreate = [];

            foreach ($cart->items as $item) {
                if (!$item->product) continue;

                if ($item->product->stock_quantity < $item->quantity) {
                    throw new Exception("Estoque insuficiente para o produto {$item->product->name}");
                }

                $subTotal += $item->quantity * $item->product->price;
                $itemsToCreate[] = $item;
            }

            $discount = 0;
            if (!empty($couponCode)) {
                $coupon = $this->couponService->getValidCoupon($couponCode);
                
                if ($coupon) {
                    $alreadyUsed = CouponUsage::where('user_id', $userId)
                        ->where('coupon_code', $coupon->code)
                        ->exists();

                    if ($alreadyUsed) throw new Exception("Cupom já utilizado.");

                    $discount = $subTotal * ($coupon->discount_percentage / 100);
                }
            }

            $totalAmount = $subTotal - $discount + $shippingCost;

            if ($totalAmount < self::MIN_ORDER_AMOUNT) 
                throw new Exception("O valor total do pedido deve ser no mínimo " . number_format(self::MIN_ORDER_AMOUNT, 2));
            
            if ($totalAmount > self::MAX_ORDER_AMOUNT)
                throw new Exception("O valor do pedido excede o limite de segurança de " . number_format(self::MAX_ORDER_AMOUNT, 2));

            $formattedAddress = 
                "{$addressData['street']}, {$addressData['number']} - {$addressData['complement']} - {$addressData['neighborhood']}, " .
                "{$addressData['city']}/{$addressData['state']} (Ref: {$addressData['reference']}) - A/C: {$addressData['receiverName']} - Tel: {$addressData['phoneNumber']}";

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

            foreach ($itemsToCreate as $cartItem) {
                $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->price
                ]);
            }

            $this->addAuditLog($order, 'Pendente', 'Pedido criado via Checkout', $userId);

            if ($discount > 0 && $couponCode) {
                CouponUsage::create([
                    'user_id' => $userId,
                    'coupon_code' => strtoupper($couponCode),
                    'order_id' => $order->id,
                    'used_at' => now()
                ]);
            }

            $cart->items()->delete();
            $cart->touch();

            $this->sendOrderReceivedEmail($userId, $order);

            $order->payment_warning = "Atenção: A reserva dos itens e o débito no estoque só ocorrem após a confirmação do pagamento.";

            return $order;
        });
    }

    public function getAllOrders(int $page, int $pageSize)
    {
        return Order::with(['items', 'history', 'user'])
            ->orderBy('order_date', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function getOrderById(string $orderId, string $userId)
    {
        return Order::with(['items.product', 'address']) 
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->firstOrFail(); 
    }

    public function getUserOrders($userId, $page, $pageSize)
    {
        return Order::with(['items', 'history'])
            ->where('user_id', $userId)
            ->orderBy('order_date', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function confirmPaymentViaWebhook(string $orderId, string $transactionId, int $amountPaidInCents)
    {
        $existingOrder = Order::where('stripe_payment_intent_id', $transactionId)->first();
        if ($existingOrder && $existingOrder->status === 'Pago') {
            Log::warning("[Webhook] Reprocessamento ignorado: {$transactionId}");
            return;
        }

        DB::transaction(function () use ($orderId, $transactionId, $amountPaidInCents) {
            $order = Order::with('items')->lockForUpdate()->find($orderId);

            if (!$order) {
                Log::error("[Webhook] Pedido {$orderId} não encontrado.");
                return;
            }

            // CORREÇÃO DE ARREDONDAMENTO
            $expectedAmount = (int) round($order->total_amount * 100);
            
            if ($expectedAmount !== $amountPaidInCents) {
                $this->notifySecurityTeam($order, $transactionId, $expectedAmount, $amountPaidInCents);
                throw new Exception("Divergência de valores. Esperado: {$expectedAmount}, Recebido: {$amountPaidInCents}");
            }

            if ($order->status === 'Pago') return;

            $productIds = $order->items->pluck('product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $outOfStockItems = [];

            foreach ($order->items as $item) {
                $product = $products[$item->product_id] ?? null;
                if (!$product || $product->stock_quantity < $item->quantity) {
                    $outOfStockItems[] = $item->product_name;
                }
            }

            if (count($outOfStockItems) > 0) {
                Log::warning("[Webhook] Estoque insuficiente Order {$orderId}. Estornando...");
                
                try {
                    $this->paymentService->refund($transactionId);

                    $order->update([
                        'stripe_payment_intent_id' => $transactionId,
                        'status' => 'Cancelado'
                    ]);

                    $this->addAuditLog($order, 'Cancelado', 
                        "Cancelamento Automático: Estoque insuficiente (" . implode(', ', $outOfStockItems) . "). Valor estornado.", 
                        "SYSTEM-STOCK-CHECK"
                    );

                    $this->sendEmailHelper($order->user, 'OrderCancelledOutOfStock', ['items' => $outOfStockItems]);
                    return;
                } catch (Exception $ex) {
                    throw $ex;
                }
            }

            foreach ($order->items as $item) {
                $product = $products[$item->product_id];
                $product->debitStock($item->quantity);
            }

            $order->update([
                'stripe_payment_intent_id' => $transactionId,
                'status' => 'Pago'
            ]);

            $this->addAuditLog($order, 'Pago', 
                "Pagamento confirmado e Estoque debitado. Transaction: {$transactionId}", 
                "STRIPE-WEBHOOK"
            );

            $this->sendOrderUpdateEmail($order, 'Pago');
        });
    }

    public function updateAdminOrder(string $orderId, array $dto)
    {
        $adminUser = Auth::user();
        if (!$adminUser || $adminUser->role !== 'Admin') {
            throw new Exception("Acesso negado. Apenas administradores podem alterar pedidos.");
        }

        return DB::transaction(function () use ($orderId, $dto, $adminUser) {
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status;
            $newStatus = $dto['status'];
            $auditMessage = "Status alterado manualmente para {$newStatus}";

            if ($newStatus === 'Aguardando Devolução') {
                if (!empty($dto['reverseLogisticsCode'])) 
                    $order->reverse_logistics_code = $dto['reverseLogisticsCode'];
                
                $order->return_instructions = $dto['returnInstructions'] ?? "Instruções padrão de devolução...";
                $auditMessage .= ". Instruções geradas.";
            }

            if (in_array($newStatus, ['Reembolso Reprovado', 'Reembolsado', 'Reembolsado Parcialmente', 'Cancelado'])) {
                if (!empty($dto['refundRejectionReason'])) 
                    $order->refund_rejection_reason = $dto['refundRejectionReason'];
                
                if (!empty($dto['refundRejectionProof'])) 
                    $order->refund_rejection_proof = $dto['refundRejectionProof'];
                
                if ($newStatus === 'Reembolso Reprovado') 
                    $auditMessage .= ". Justificativa anexada.";
            }

            if (in_array($newStatus, ['Reembolsado', 'Reembolsado Parcialmente', 'Cancelado']) 
                && !in_array($order->status, ['Reembolsado', 'Reembolsado Parcialmente', 'Cancelado'])
                && $order->stripe_payment_intent_id) 
            {
                $amountToRefund = 0;

                if (isset($dto['refundAmount']) && is_numeric($dto['refundAmount'])) {
                    $amountToRefund = (float) $dto['refundAmount'];
                } else {
                    $amountToRefund = $order->refund_requested_amount ?? $order->total_amount;
                }

                if ($amountToRefund > $order->total_amount)
                    throw new Exception("O valor do reembolso (R$ {$amountToRefund}) não pode ser maior que o total do pedido.");

                if ($order->refund_type === 'Parcial' && $order->refund_requested_amount) {
                    if ($amountToRefund > $order->refund_requested_amount)
                        throw new Exception("O valor do reembolso (R$ {$amountToRefund}) excede o valor calculado dos itens solicitados (R$ {$order->refund_requested_amount}).");
                }

                try {
                    $this->paymentService->refund($order->stripe_payment_intent_id, $amountToRefund);
                    $auditMessage .= ". Reembolso de R$ " . number_format($amountToRefund, 2, ',', '.') . " processado no Stripe.";

                    if ($newStatus === 'Reembolsado' && $amountToRefund < $order->total_amount) {
                        $newStatus = 'Reembolsado Parcialmente';
                    }
                } catch (Exception $ex) {
                    throw new Exception("Erro no reembolso Stripe: " . $ex->getMessage());
                }
            }

            if ($newStatus === 'Entregue' && $order->status !== 'Entregue') {
                $order->delivery_date = now();
            }

            if (!empty($dto['trackingCode'])) {
                $order->tracking_code = $dto['trackingCode'];
                $auditMessage .= " (Rastreio: {$dto['trackingCode']})";
            }

            $this->addAuditLog($order, $newStatus, $auditMessage, "Admin:{$adminUser->id}");
            
            if ($oldStatus !== $newStatus) {
                $this->sendOrderUpdateEmail($order, $newStatus);
            }
        });
    }

    public function requestRefund(string $orderId, string $userId, array $dto)
    {
        $order = Order::with('items')->where('id', $orderId)->firstOrFail();

        if ($order->user_id !== $userId) abort(403, "Acesso negado.");
        
        if (!in_array($order->status, ['Entregue', 'Pago'])) 
            throw new Exception("Status do pedido não permite solicitação de reembolso.");
        
        if ($order->refund_type) 
            throw new Exception("Já existe uma solicitação de reembolso para este pedido.");

        DB::transaction(function () use ($order, $dto, $userId) {
            $refundType = $dto['refundType'] ?? 'Total';
            
            if ($refundType === 'Parcial') {
                if (empty($dto['items'])) throw new Exception("Nenhum item selecionado para reembolso parcial.");
                
                $calculatedAmount = 0;
                $discountRatio = $order->sub_total > 0 ? $order->discount / $order->sub_total : 0;

                foreach ($dto['items'] as $reqItem) {
                    $item = $order->items->firstWhere('product_id', $reqItem['productId']);
                    if (!$item) throw new Exception("Produto {$reqItem['productId']} não pertence ao pedido.");
                    
                    if ($reqItem['quantity'] > $item->quantity || $reqItem['quantity'] <= 0)
                        throw new Exception("Quantidade inválida para {$item->product_name}.");
                    
                    $item->refund_quantity = $reqItem['quantity'];
                    $item->save();

                    $effectiveUnitPrice = $item->unit_price * (1 - $discountRatio);
                    $calculatedAmount += $effectiveUnitPrice * $reqItem['quantity'];
                }
                
                $order->refund_type = 'Parcial';
                $order->refund_requested_amount = round($calculatedAmount, 2);
                $this->addAuditLog($order, 'Reembolso Solicitado', "Cliente solicitou reembolso PARCIAL de R$ " . number_format($order->refund_requested_amount, 2, ',', '.'), $userId);
            } else {
                $order->refund_type = 'Total';
                $order->refund_requested_amount = $order->total_amount;
                
                foreach($order->items as $i) { 
                    $i->refund_quantity = $i->quantity; 
                    $i->save(); 
                }
                
                $this->addAuditLog($order, 'Reembolso Solicitado', "Cliente solicitou reembolso TOTAL.", $userId);
            }
            $order->save();
        });
    }

    private function addAuditLog(Order $order, string $newStatus, string $message, string $changedBy)
    {
        $order->status = $newStatus;
        $order->save();

        $order->history()->create([
            'status' => $newStatus,
            'message' => $message,
            'changed_by' => $changedBy,
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    private function notifySecurityTeam(Order $order, string $transactionId, int $expectedAmount, int $receivedAmount)
    {
        try {
            $securityEmail = config('mail.admin_email');
            
            if ($securityEmail) {
                $this->emailService->send($securityEmail, 'SecurityAlertPaymentMismatch', [
                    'OrderId' => $order->id,
                    'UserEmail' => $order->user->email ?? "N/A",
                    'UserId' => $order->user_id,
                    'TransactionId' => $transactionId,
                    'ExpectedAmount' => $expectedAmount / 100.0,
                    'ReceivedAmount' => $receivedAmount / 100.0,
                    'Divergence' => abs($expectedAmount - $receivedAmount) / 100.0,
                    'Date' => now(),
                    'Year' => date('Y')
                ]);
            }
        } catch (Exception $ex) {
            Log::error("Erro ao notificar segurança: " . $ex->getMessage());
        }
    }

    private function sendOrderReceivedEmail(string $userId, Order $order)
    {
        if ($order->user && $order->user->email) {
            $this->sendEmailHelper($order->user, 'OrderReceived', [
                'name' => $order->user->full_name,
                'orderNumber' => $order->id,
                'total' => number_format($order->total_amount, 2, ',', '.'),
                'items' => $order->items->map(fn($i) => [
                    'productName' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price' => number_format($i->unit_price, 2, ',', '.')
                ])
            ]);
        }
    }

    private function sendOrderUpdateEmail(Order $order, string $newStatus)
    {
        $templateKey = match($newStatus) {
            'Pago' => 'PaymentConfirmed',
            'Enviado' => 'OrderShipped',
            'Entregue' => 'OrderDelivered',
            'Cancelado' => 'OrderCanceled',
            'Reembolsado' => 'OrderRefunded',
            'Reembolsado Parcialmente' => 'OrderPartiallyRefunded',
            'Aguardando Devolução' => 'OrderReturnInstructions',
            'Reembolso Reprovado' => 'OrderRefundRejected',
            default => null
        };

        if ($templateKey && $order->user) {
            $this->sendEmailHelper($order->user, $templateKey, [
                'orderNumber' => $order->id,
                'trackingCode' => $order->tracking_code,
                'reverseLogisticsCode' => $order->reverse_logistics_code,
                'returnInstructions' => $order->return_instructions,
                'refundRejectionReason' => $order->refund_rejection_reason,
                'refundRejectionProof' => $order->refund_rejection_proof,
            ]);
        }
    }

    private function sendEmailHelper($user, $templateKey, $data)
    {
        try {
            $data['Name'] = $user->full_name;
            $data['Year'] = date('Y');
            $this->emailService->send($user->email, $templateKey, $data);
        } catch (Exception $e) {
            Log::error("Erro envio email {$templateKey}: " . $e->getMessage());
        }
    }
}