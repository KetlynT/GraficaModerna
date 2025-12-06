using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Interfaces;

public interface IShippingService
{

    Task<List<ShippingOptionDto>> CalculateAsync(string destinationCep, List<ShippingItemDto> items);
}
