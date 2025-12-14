using GraficaModerna.Domain.Models;

namespace GraficaModerna.Domain.Interfaces;

public interface IDashboardRepository
{
    Task<DashboardAnalytics> GetAnalyticsAsync();
}