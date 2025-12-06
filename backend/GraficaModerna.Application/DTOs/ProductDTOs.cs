using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public record CreateProductDto(
    [Required(ErrorMessage = "Nome � obrigat�rio")]
    string Name,
    string Description,
    [Range(0.01, double.MaxValue, ErrorMessage = "Pre�o deve ser maior que zero")]
    decimal Price,
    string ImageUrl,
    [Range(0.001, double.MaxValue, ErrorMessage = "Peso inv�lido (kg)")]
    decimal Weight,
    [Range(1, int.MaxValue, ErrorMessage = "Largura inv�lida (cm)")]
    int Width,
    [Range(1, int.MaxValue, ErrorMessage = "Altura inv�lida (cm)")]
    int Height,
    [Range(1, int.MaxValue, ErrorMessage = "Comprimento inv�lido (cm)")]
    int Length,
    [Range(0, int.MaxValue, ErrorMessage = "Estoque inv�lido")]
    int StockQuantity
);

public record ProductResponseDto(
    Guid Id,
    string Name,
    string Description,
    decimal Price,
    string ImageUrl,
    decimal Weight,
    int Width,
    int Height,
    int Length,
    int StockQuantity 
);
