using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Interfaces;

namespace GraficaModerna.Application.Services;

public class DashboardService(IDashboardRepository repository) : IDashboardService
{
    private readonly IDashboardRepository _repository = repository;

    public async Task<DashboardStatsDto> GetStatsAsync()
    {
        var data = await _repository.GetAnalyticsAsync();

        var lowStockDtos = data.LowStockProducts
            .Select(p => new LowStockProductDto(p.Id, p.Name, p.StockQuantity))
            .ToList();

        var recentOrderDtos = data.RecentOrders
            .Select(o => new RecentOrderDto(
                o.Id,
                o.TotalAmount,
                o.Status,
                o.OrderDate,
                o.CustomerName,
                o.CustomerEmail
            ))
            .ToList();

        return new DashboardStatsDto(
            data.TotalOrders,
            data.TotalRevenue,
            data.TotalRefunded,
            data.PendingOrders,
            lowStockDtos,
            recentOrderDtos
        );
    }
}