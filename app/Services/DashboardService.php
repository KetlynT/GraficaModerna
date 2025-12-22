<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getAnalytics(string $range = '7d')
    {
        $startDate = match ($range) {
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y'  => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(7),
        };

        $totalRevenue = Order::where('status', '!=', 'Cancelado')->sum('total_amount');
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalCustomers = User::where('role', 'User')->count();

        $salesChart = Order::select(
                DB::raw('DATE(created_at) as date'), 
                DB::raw('SUM(total_amount) as total')
            )
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'Cancelado')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'value' => (float)$item->total
            ]);

        $lowStockProducts = Product::where('stock_quantity', '<', 10)
            ->where('is_active', true)
            ->select('id', 'name', 'stock_quantity', 'image_urls')
            ->limit(5)
            ->get();

        $recentOrders = Order::with('user:id,full_name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'totalRevenue' => (float)$totalRevenue,
            'totalOrders' => $totalOrders,
            'totalProducts' => $totalProducts,
            'totalCustomers' => $totalCustomers,
            'salesChart' => $salesChart,
            'lowStockProducts' => $lowStockProducts,
            'recentOrders' => $recentOrders
        ];
    }
}