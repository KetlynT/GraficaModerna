using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Interfaces;

public interface IDashboardService
{
    Task<DashboardStatsDto> GetStatsAsync();
}