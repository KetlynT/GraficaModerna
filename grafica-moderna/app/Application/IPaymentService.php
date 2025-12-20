<?php

namespace App\Application\Interfaces;

use App\Models\Order;

interface IPaymentService
{
    public function createCheckoutSession(Order $order): string;

    public function refundPayment(string $paymentIntentId, ?float $amount = null): void;
}