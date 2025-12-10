using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Infrastructure.Context;
using GraficaModerna.Domain.Enums;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Services;

public class DashboardService(AppDbContext context) : IDashboardService
{
    private readonly AppDbContext _context = context;

    public async Task<DashboardStatsDto> GetStatsAsync()
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
            .Where(p => p.IsActive && p.StockQuantity < 10)
            .Select(p => new LowStockProductDto(p.Id, p.Name, p.StockQuantity))
            .OrderBy(p => p.StockQuantity)
            .Take(5)
            .ToListAsync();

        var recentOrders = await _context.Orders
            .Include(o => o.User)
            .OrderByDescending(o => o.OrderDate)
            .Take(5)
            .Select(o => new RecentOrderDto(
                o.Id,
                o.TotalAmount,
                o.Status,
                o.OrderDate,
                o.User != null ? o.User.FullName : "Cliente Desconhecido",
                (o.User != null ? o.User.Email : null) ?? ""
            ))
            .ToListAsync();

        return new DashboardStatsDto(totalOrders, totalRevenue, totalRefunded, pendingOrders, lowStockProducts, recentOrders);
    }
}