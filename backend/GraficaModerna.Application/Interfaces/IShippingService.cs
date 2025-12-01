using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Interfaces;

public interface IShippingService
{
    // O CEP de origem virá das configurações do sistema, não do parâmetro
    Task<List<ShippingOptionDto>> CalculateAsync(string destinationCep, List<ShippingItemDto> items);
}