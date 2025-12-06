using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public class CalculateShippingRequest
{
    public string DestinationCep { get; set; } = string.Empty;
    public List<ShippingItemDto> Items { get; set; } = [];
}

public class ShippingItemDto
{
    public Guid ProductId { get; set; } // Adicionado para segurança
    public decimal Weight { get; set; } // Será ignorado/sobrescrito pelo Controller
    public int Width { get; set; }      // Será ignorado/sobrescrito pelo Controller
    public int Height { get; set; }     // Será ignorado/sobrescrito pelo Controller
    public int Length { get; set; }     // Será ignorado/sobrescrito pelo Controller
    public int Quantity { get; set; }
}

public class ShippingOptionDto
{
    public string Name { get; set; } = string.Empty;
    public decimal Price { get; set; }
    public int DeliveryDays { get; set; }
    public string? Provider { get; set; }
    public string? Error { get; set; }
}