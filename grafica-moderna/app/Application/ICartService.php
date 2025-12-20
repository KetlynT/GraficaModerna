<?php

namespace App\Application\Interfaces;

interface ICartService
{
    public function getCart(string $userId): object;

    public function addItem(string $userId, array $dto): void;

    public function updateItemQuantity(string $userId, string $cartItemId, int $quantity): void;

    public function removeItem(string $userId, string $cartItemId): void;

    public function clearCart(string $userId): void;
}