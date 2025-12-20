<?php

namespace App\Application\Interfaces;

use App\Models\Coupon;

interface ICouponService
{
    public function create(array $dto): object;

    public function getAll(): iterable;

    public function delete(string $id): void;

    public function getValidCoupon(string $code): ?Coupon;
}