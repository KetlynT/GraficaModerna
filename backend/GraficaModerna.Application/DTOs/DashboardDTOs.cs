using GraficaModerna.Domain.Enums;

namespace GraficaModerna.Application.DTOs;

public record DashboardStatsDto(
    int TotalOrders,
    decimal TotalRevenue,
    decimal TotalRefunded,
    int PendingOrders,
    List<LowStockProductDto> LowStockProducts,
    List<RecentOrderDto> RecentOrders
);

public record LowStockProductDto(Guid Id, string Name, int StockQuantity);

public record RecentOrderDto(
    Guid Id,
    decimal TotalAmount,
    OrderStatus Status,
    DateTime Date,
    string CustomerName,
    string CustomerEmail
);