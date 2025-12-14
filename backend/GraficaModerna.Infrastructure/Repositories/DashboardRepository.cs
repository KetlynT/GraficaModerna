using GraficaModerna.Domain.Enums;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Domain.Models;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Repositories;

public class DashboardRepository(AppDbContext context) : IDashboardRepository
{
    private readonly AppDbContext _context = context;

    public async Task<DashboardAnalytics> GetAnalyticsAsync()
    {
        var totalOrders = await _context.Orders.CountAsync();

        var totalRevenue = await _context.Orders
            .Where(o => o.Status != OrderStatus.Cancelado && o.Status != OrderStatus.Reembolsado)
            .SumAsync(o => o.TotalAmount);

        var totalRefunded = await _context.Orders
            .Where(o => o.Status == OrderStatus.Reembolsado)
            .SumAsync(o => o.TotalAmount);

        var pendingOrders = await _context.Orders.CountAsync(o => o.Status == OrderStatus.Pendente);

        var lowStockProducts = await _context.Products
            .AsNoTracking()
            .Where(p => p.IsActive && p.StockQuantity < 10)
            .OrderBy(p => p.StockQuantity)
            .Take(5)
            .Select(p => new LowStockItem(p.Id, p.Name, p.StockQuantity))
            .ToListAsync();

        var recentOrders = await _context.Orders
            .AsNoTracking()
            .Include(o => o.User)
            .OrderByDescending(o => o.OrderDate)
            .Take(5)
            .Select(o => new RecentOrderSummary(
                o.Id,
                o.TotalAmount,
                o.Status,
                o.OrderDate,
                o.User != null ? o.User.FullName : "Cliente Desconhecido",
                (o.User != null ? o.User.Email : null) ?? ""
            ))
            .ToListAsync();

        return new DashboardAnalytics(
            totalOrders,
            totalRevenue,
            totalRefunded,
            pendingOrders,
            lowStockProducts,
            recentOrders
        );
    }
}