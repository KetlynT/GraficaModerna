using GraficaModerna.Domain.Enums;

namespace GraficaModerna.Domain.Models;

public record DashboardAnalytics(
    int TotalOrders,
    decimal TotalRevenue,
    decimal TotalRefunded,
    int PendingOrders,
    List<LowStockItem> LowStockProducts,
    List<RecentOrderSummary> RecentOrders
);

public record LowStockItem(
    Guid Id,
    string Name,
    int StockQuantity
);

public record RecentOrderSummary(
    Guid Id,
    decimal TotalAmount,
    OrderStatus Status,
    DateTime OrderDate,
    string CustomerName,
    string CustomerEmail
);