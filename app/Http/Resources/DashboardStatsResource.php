<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totalOrders' => (int) $this['totalOrders'],
            'totalRevenue' => (float) $this['totalRevenue'],
            'totalRefunded' => (float) ($this['totalRefunded'] ?? 0),
            'pendingOrders' => (int) ($this['pendingOrders'] ?? 0),
            'lowStockProducts' => collect($this['lowStockProducts'])->map(fn($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'stockQuantity' => (int) $p['stock_quantity'],
                'imageUrl' => $p['image_urls'][0] ?? null
            ]),
            'recentOrders' => collect($this['recentOrders'])->map(fn($o) => [
                'id' => $o['id'],
                'totalAmount' => (float) $o['total_amount'],
                'status' => $o['status'],
                'orderDate' => $o['created_at'],
                'customerName' => $o['user']['full_name'] ?? 'N/A',
                'customerEmail' => $o['user']['email'] ?? 'N/A'
            ]),
        ];
    }
}