using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

// Input (Request)
public record CreateProductDto(
	[Required(ErrorMessage = "Nome é obrigatório")] string Name,
	string Description,
	[Range(0.01, double.MaxValue, ErrorMessage = "Preço deve ser maior que zero")] decimal Price,
	string ImageUrl
);

// Output (Response)
public record ProductResponseDto(
	Guid Id,
	string Name,
	string Description,
	decimal Price,
	string ImageUrl
);