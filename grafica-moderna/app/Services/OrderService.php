<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Services\MelhorEnvioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            $order->history()->create([
                'status' => 'Pendente',
                'message' => 'Pedido criado via Checkout',
                'changed_by' => $userId,
                'timestamp' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

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
            return;
        }

        DB::transaction(function () use ($orderId, $transactionId, $amountPaidInCents) {
            $order = Order::with('items')->lockForUpdate()->find($orderId);

            if (!$order) {
                return;
            }

            $expectedAmount = (int)($order->total_amount * 100);
            if ($expectedAmount !== $amountPaidInCents) {
                $this->notifySecurityTeam($order, $transactionId, $expectedAmount, $amountPaidInCents);
                throw new Exception("Divergência de valores de segurança. Esperado: {$expectedAmount}, Recebido: {$amountPaidInCents}");
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

                    $this->sendEmailHelper($order->user, 'OrderCancelledOutOfStock', [
                        'items' => $outOfStockItems
                    ]);
                    
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

    public function requestRefund(string $orderId, string $userId, array $dto)
    {
        $order = Order::with('items')->where('id', $orderId)->firstOrFail();

        if ($order->user_id !== $userId) {
            abort(403, "Você não tem permissão para acessar este pedido.");
        }

        if (!in_array($order->status, ['Entregue', 'Pago'])) {
            throw new Exception("Status do pedido não permite solicitação de reembolso.");
        }

        if ($order->refund_type) {
            throw new Exception("Já existe uma solicitação de reembolso para este pedido.");
        }

        DB::transaction(function () use ($order, $dto, $userId) {
            $calculatedRefundAmount = 0;
            $refundType = $dto['refundType'] ?? 'Total';

            if ($refundType === 'Parcial') {
                if (empty($dto['items'])) throw new Exception("Nenhum item selecionado para reembolso parcial.");

                $discountRatio = $order->sub_total > 0 ? $order->discount / $order->sub_total : 0;

                foreach ($dto['items'] as $itemRequest) {
                    $orderItem = $order->items->firstWhere('product_id', $itemRequest['productId']);
                    
                    if (!$orderItem) throw new Exception("Produto {$itemRequest['productId']} não pertence a este pedido.");
                    
                    if ($itemRequest['quantity'] > $orderItem->quantity || $itemRequest['quantity'] <= 0) {
                        throw new Exception("Quantidade inválida para o produto {$orderItem->product_name}.");
                    }

                    $orderItem->refund_quantity = $itemRequest['quantity'];
                    $orderItem->save();

                    $effectiveUnitPrice = $orderItem->unit_price * (1 - $discountRatio);
                    $calculatedRefundAmount += $effectiveUnitPrice * $itemRequest['quantity'];
                }

                $calculatedRefundAmount = round($calculatedRefundAmount, 2);
                
                $order->refund_type = 'Parcial';
                $order->refund_requested_amount = $calculatedRefundAmount;

                $this->addAuditLog($order, 'Reembolso Solicitado', 
                    "Cliente solicitou reembolso PARCIAL de R$ " . number_format($calculatedRefundAmount, 2, ',', '.'), 
                    $userId
                );

            } else {
                $order->refund_type = 'Total';
                $order->refund_requested_amount = $order->total_amount;
                
                foreach ($order->items as $item) {
                    $item->refund_quantity = $item->quantity;
                    $item->save();
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
                    'CustomerIp' => $order->customer_ip ?? "N/A",
                    'UserAgent' => $order->user_agent ?? "N/A",
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
            // Adiciona dados comuns
            $data['Name'] = $user->full_name;
            $data['Year'] = date('Y');
            
            // Chama o EmailService diretamente, sem renderizar aqui
            $this->emailService->send($user->email, $templateKey, $data);
        } catch (Exception $e) {
            Log::error("Erro ao enviar email {$templateKey}: " . $e->getMessage());
        }
    }
}