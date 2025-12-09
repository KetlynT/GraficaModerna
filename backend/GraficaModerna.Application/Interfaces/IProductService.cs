using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Interfaces;

public interface IProductService
{
    Task<PagedResultDto<ProductResponseDto>> GetCatalogAsync(string? search, string? sort, string? order, int page,
        int pageSize);

    Task<ProductResponseDto> GetByIdAsync(Guid id);
    Task<ProductResponseDto> CreateAsync(CreateProductDto dto);
    Task UpdateAsync(Guid id, UpdateProductDto dto);
    Task DeleteAsync(Guid id);
}
