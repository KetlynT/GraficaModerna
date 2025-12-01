namespace GraficaModerna.Application.DTOs;

public class CalculateShippingRequest
{
    public string DestinationCep { get; set; } = string.Empty;
    public List<ShippingItemDto> Items { get; set; } = new();
}

public class ShippingItemDto
{
    public decimal Weight { get; set; }
    public int Width { get; set; }
    public int Height { get; set; }
    public int Length { get; set; }
    public int Quantity { get; set; }
}

public class ShippingOptionDto
{
    public string Name { get; set; } = string.Empty; // ex: PAC, SEDEX
    public decimal Price { get; set; }
    public int DeliveryDays { get; set; }
    public string? Provider { get; set; } // ex: Correios
    public string? Error { get; set; }
}