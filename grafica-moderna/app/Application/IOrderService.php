<?php

namespace App\Application\Interfaces;

use App\Models\Order;

interface IOrderService
{
    public function createOrderFromCart(
        string $userId, 
        array $addressDto, 
        ?string $couponCode,
        string $shippingMethod
    ): object;

    /**
     * @return array{items: array, total: int, page: int, pageSize: int}
     */
    public function getUserOrders(string $userId, int $page, int $pageSize): array;

    public function getAll(int $page, int $pageSize): array;

    public function updateAdminOrder(string $orderId, string $status): void;

    public function confirmPaymentViaWebhook(string $orderId, string $transactionId, int $amountPaidInCents): void;

    public function requestRefund(string $orderId, string $userId, array $dto): void;

    public function getOrderForPayment(string $orderId, string $userId): ?Order;
    
    // public function getPaymentStatus... (Geralmente coberto pelo GetById ou GetOrderForPayment)
}