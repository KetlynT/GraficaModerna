<?php

namespace App\Application\Interfaces;

interface IDashboardService
{
    /**
     * @return array{
     * totalOrders: int,
     * totalRevenue: float,
     * totalProducts: int,
     * recentOrders: array
     * }
     */
    public function getStats(): array;
}