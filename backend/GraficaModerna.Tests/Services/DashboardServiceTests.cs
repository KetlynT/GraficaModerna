using GraficaModerna.Application.Services;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Domain.Models;
using Moq;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class DashboardServiceTests
{
    private readonly Mock<IDashboardRepository> _repoMock;
    private readonly DashboardService _service;

    public DashboardServiceTests()
    {
        _repoMock = new Mock<IDashboardRepository>();
        _service = new DashboardService(_repoMock.Object);
    }

    [Fact]
    public async Task GetStatsAsync_ShouldReturnDataFromRepository()
    {
        var lowStockId = Guid.NewGuid();

        var lowStockList = new List<LowStockItem>
        {
            new(lowStockId, "P2", 5)
        };

        var recentOrdersList = new List<RecentOrderSummary>();

        var expectedStats = new DashboardAnalytics(
            10,
            300m,
            50m,
            2,
            lowStockList,
            recentOrdersList
        );

        _repoMock.Setup(r => r.GetAnalyticsAsync())
            .ReturnsAsync(expectedStats);

        var stats = await _service.GetStatsAsync();

        Assert.Equal(300m, stats.TotalRevenue);
        Assert.Equal(50m, stats.TotalRefunded);
        Assert.Equal(10, stats.TotalOrders);
        Assert.Single(stats.LowStockProducts);
        Assert.Equal("P2", stats.LowStockProducts[0].Name);

        _repoMock.Verify(r => r.GetAnalyticsAsync(), Times.Once);
    }
}